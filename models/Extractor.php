<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\models;

use DirectoryIterator;
use gplcart\core\Model;

/**
 * Methods to extract translatable strings from various source files
 */
class Extractor extends Model
{

    /**
     * Max parsing width in columns
     */
    const MAX_COLS = 500;

    /**
     * Max rows to parse per file
     */
    const MAX_LINES = 5000;

    /**
     * Pattern to extract strings from JS function Gplcart.text()
     */
    const PATTERN_JS = '/Gplcart.text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s';

    /**
     * Pattern to extract strings from TWIG function {{ text() }}
     */
    const PATTERN_TWIG = '/text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s';

    /**
     * Pattern to extract strings using inline @text annotation
     */
    const PATTERN_PHPDOC = '/\/\*(?:\*|\s)+@text(?:\*|\s)+\*\/\s*([\'"])(.+?)\1\s*/';

    /**
     * Pattern to extract strings from Language::text() method
     */
    const PATTERN_PHP = '/->text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an extractor by a file extension or an array of extractors keyed by extension
     * @param null|string $extension
     * @return array|string
     */
    public function get($extension = null)
    {
        $extractor = &gplcart_static(__METHOD__ . "$extension");

        if (isset($extractor)) {
            return $extractor;
        }

        $extractors = $this->getDefault();

        if (isset($extension)) {
            $extractor = empty($extractors[$extension]) ? '' : $extractors[$extension];
        } else {
            $extractor = $extractors;
        }

        $this->hook->attach('module.extractor.get', $extractor, $extension, $this);
        return $extractor;
    }

    /**
     * Returns an array of default extractors keyed by supported file extension
     * @return array
     */
    protected function getDefault()
    {
        return array(
            'json' => array($this, 'extractFromFileJson'),
            'js' => array(static::PATTERN_JS),
            'twig' => array(static::PATTERN_TWIG, static::PATTERN_JS),
            'php' => array(static::PATTERN_PHP, static::PATTERN_JS, static::PATTERN_PHPDOC)
        );
    }

    /**
     * Extract strings from module.json
     * @param string $file
     * @return array
     */
    protected function extractFromFileJson($file)
    {
        $extracted = array();
        if (basename($file) === 'module.json') {
            $content = json_decode(file_get_contents($file), true);
            if (!empty($content['name'])) {
                $extracted[] = $content['name'];
            }

            if (!empty($content['description'])) {
                $extracted[] = $content['description'];
            }
        }

        return $extracted;
    }

    /**
     * Returns an array of extracted strings from a file
     * @param string $file
     * @return array
     */
    public function extractFromFile($file)
    {
        $extractor = $this->get(pathinfo($file, PATHINFO_EXTENSION));

        if (empty($extractor)) {
            return array();
        }

        if (is_callable($extractor)) {
            return call_user_func_array($extractor, array($file));
        }

        $handle = fopen($file, 'r');

        if (!is_resource($handle)) {
            return array();
        }

        $lines = self::MAX_LINES;

        $extracted = array();
        while ($lines && $line = fgets($handle, self::MAX_COLS)) {
            $extracted = array_merge($extracted, $this->extractFromString($line, $extractor));
            $lines--;
        }

        fclose($handle);
        return $extracted;
    }

    /**
     * Returns an array of extracted strings from a source string
     * @param string $string
     * @param string|array $regexp_patterns
     * @return array
     */
    public function extractFromString($string, $regexp_patterns)
    {
        $result = null;
        $this->hook->attach('module.extractor.extract', $string, $regexp_patterns, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $extracted = array();
        foreach ((array) $regexp_patterns as $pattern) {
            $matches = array();
            preg_match_all($pattern, $string, $matches);
            if (!empty($matches[2])) {
                $extracted = array_merge($extracted, $this->clean($matches[2]));
            }
        }

        return $extracted;
    }

    /**
     * Clean up an array of extracted strings or a single string
     * @param array|string $items
     * @return array
     */
    protected function clean($items)
    {
        $cleaned = array();
        foreach ((array) $items as $item) {
            // Remove double/single quotes and whitespaces from the beginning and end of a string
            $str = trim(preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $item));
            if ($str !== '') {
                $cleaned[] = $str;
            }
        }

        return $cleaned;
    }

    /**
     * Returns an array of scanned files to extract from or counts them
     * @param array $options
     * @return integer|array
     */
    public function scan(array $options)
    {
        $result = null;
        $this->hook->attach('module.extractor.scan', $options, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $scanned = array();
        foreach ((array) $options['directory'] as $directory) {
            $scanned = array_merge($scanned, $this->scanRecursive($directory));
        }

        $files = array_filter($scanned, function($file) {
            return $this->isSupportedFile($file);
        });

        if (!empty($options['count'])) {
            return count($files);
        }

        sort($files);
        return $files;
    }

    /**
     * Whether the file can be parsed
     * @param string $file
     * @return bool
     */
    public function isSupportedFile($file)
    {
        return is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), array_keys($this->get()));
    }

    /**
     * Returns an array of directories to be scanned
     * @return array
     */
    public function getScannedDirectories()
    {
        $directories = array(
            GC_DIR_CORE,
            GC_DIR_CONFIG,
            $this->config->getModuleDirectory('frontend'),
            $this->config->getModuleDirectory('backend')
        );

        $this->hook->attach('module.extractor.directories', $directories, $this);
        return $directories;
    }

    /**
     * Recursive scans files in a directory
     * @param string $directory
     * @param array $results
     * @return array
     */
    protected function scanRecursive($directory, &$results = array())
    {
        $directory = gplcart_path_normalize($directory);

        if (strpos($directory, '/override/') !== false) {
            return $results; // Exclude "override" directories
        }

        if (strpos($directory, '/vendor/') !== false) {
            return $results; // Exclude "vendor" directories
        }

        foreach (new DirectoryIterator($directory) as $file) {
            $realpath = $file->getRealPath();
            if ($file->isDir() && !$file->isDot()) {
                $this->scanRecursive($realpath, $results);
                $results[] = $realpath;
            } else if ($file->isFile()) {
                $results[] = $realpath;
            }
        }

        return $results;
    }

}

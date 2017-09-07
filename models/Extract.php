<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\models;

use gplcart\core\Model;

/**
 * Methods to extract translatable strings from various source files
 */
class Extract extends Model
{

    /**
     * Max parsing width (in columns)
     */
    const MAX_COLS = 500;

    /**
     * Max parsing depth (in rows)
     */
    const MAX_LINES = 5000;

    /**
     * An array of REGEXP patterns keyed by file extension
     * @var array
     */
    protected $patterns = array(
        'twig' => '/text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s', // {{ text('Text to translate') }}
        'js' => '/GplCart.text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s', // GplCart.text('Text to translate');
        'php' => array(
            '/->text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s', // $this->language->text('Text to translate');
            '/\/\*(?:\*|\s)+@text(?:\*|\s)+\*\/\s*([\'"])(.+?)\1\s*/', // /* @text */ 'Text to translate'
        )
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns a pattern by a file extension or an array of pattern keyed by extension
     * @param null|string $extension
     * @return array|string
     */
    public function getPattern($extension = null)
    {
        $pattern = &gplcart_static(__METHOD__ . "$extension");

        if (isset($pattern)) {
            return $pattern;
        }

        if (isset($extension)) {
            $pattern = empty($this->patterns[$extension]) ? '' : $this->patterns[$extension];
        } else {
            $pattern = $this->patterns;
        }

        $this->hook->attach('module.extractor.pattern', $pattern, $extension, $this);
        return $pattern;
    }

    /**
     * Returns an array of extracted strings from a file
     * @param string $file
     * @return array
     */
    public function extractFromFile($file)
    {
        $pattern = $this->getPattern(pathinfo($file, PATHINFO_EXTENSION));

        if (empty($pattern)) {
            return array();
        }

        $handle = fopen($file, 'r');

        if (!is_resource($handle)) {
            return array();
        }

        $lines = self::MAX_LINES;

        $extracted = array();
        while ($lines && $line = fgets($handle, self::MAX_COLS)) {
            $extracted = array_merge($extracted, $this->extractFromString($line, $pattern));
            $lines--;
        }

        fclose($handle);
        return $extracted;
    }

    /**
     * Returns an array of extracted strings from a source string
     * @param string $string
     * @param string|array $patterns
     * @return array
     */
    public function extractFromString($string, $patterns)
    {
        $result = null;
        $this->hook->attach('module.extractor.extract', $string, $patterns, $result, $this);

        if (isset($result)) {
            return $result;
        }

        foreach ((array) $patterns as $pattern) {
            $matches = array();
            preg_match_all($pattern, $string, $matches);
            if (!empty($matches[2])) {
                return $this->clean($matches[2]);
            }
        }

        return array();
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
        return is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), array_keys($this->getPattern()));
    }

    /**
     * Returns an array of directories to be scanned
     * @return array
     */
    public function getScannedDirectories()
    {
        $directories = array(
            GC_CORE_DIR,
            GC_CONFIG_DEFAULT_DIR,
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
        if (strpos($directory, 'override') !== false) {
            return $results; // Exclude "override" directories
        }

        foreach (scandir($directory) as $file) {
            $path = "$directory/$file";
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($file != "." && $file != "..") {
                $this->scanRecursive($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }

}

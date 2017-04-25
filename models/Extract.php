<?php

/**
 * @package GPL Cart core
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
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns a pattern by a file extension
     * or an array of pattern keyed by extension
     * @return array|string
     */
    public function getPattern($ext = null)
    {
        $patterns = array(
            'twig' => '/text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s',
            'php' => '/->text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s',
            'js' => '/GplCart.text\s*\(\s*([\'"])(.+?)\1\s*([\),])/s',
        );

        if (isset($ext)) {
            return empty($patterns[$ext]) ? '' : $patterns[$ext];
        }

        return $patterns;
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
     * @param string $pattern
     * @return array
     */
    public function extractFromString($string, $pattern)
    {
        $matches = array();
        preg_match_all($pattern, $string, $matches);

        if (empty($matches[2])) {
            return array();
        }
        return $this->clean($matches[2]);
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
        $scanned = array();
        foreach ((array) $options['directory'] as $directory) {
            $scanned = array_merge($scanned, gplcart_file_scan_recursive($directory));
        }

        $files = array_filter($scanned, function($file) {
            return is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), array_keys($this->getPattern()));
        });

        if (!empty($options['count'])) {
            return count($files);
        }

        sort($files);
        return $files;
    }

}

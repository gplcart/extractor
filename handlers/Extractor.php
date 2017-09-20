<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\handlers;

use gplcart\core\models\Language as LanguageModel;
use gplcart\modules\extractor\models\Extractor as ExtractorModel;

/**
 * String extractor handler
 */
class Extractor
{

    /**
     * Extractor model instance
     * @var \gplcart\modules\extractor\models\Extractor $extractor
     */
    protected $extractor;

    /**
     * Language model class instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * @param LanguageModel $language
     * @param ExtractorModel $extractor
     */
    public function __construct(LanguageModel $language,
            ExtractorModel $extractor)
    {
        $this->language = $language;
        $this->extractor = $extractor;
    }

    /**
     * Processes one extraction job iteration
     * @param array $job
     * @return array
     */
    public function process(array &$job)
    {
        if (!isset($job['context']['offset'])) {
            $job['context']['offset'] = 0;
        }

        $scanned = $this->extractor->scan(array('directory' => $job['data']['directory']));
        $files = array_slice($scanned, $job['context']['offset'], $job['data']['limit']);

        if (empty($files)) {
            $job['status'] = false;
            $job['done'] = $job['total'];
            return $job;
        }

        foreach ($files as $file) {
            foreach ($this->extractor->extractFromFile($file) as $string) {
                if (!$this->exists($string, $job)) {
                    $job['inserted'] ++;
                    gplcart_file_csv($job['data']['file'], array($string, ''));
                }
            }
        }

        $job['context']['offset'] += count($files);
        $job['done'] = $job['context']['offset'];
        return $job;
    }

    /**
     * Check if the string already exists in the file
     * @param string $string
     * @param array $job
     * @return boolean
     */
    protected function exists($string, array $job)
    {
        // Check core translation for dublicates
        if (!empty($job['data']['check_file'])) {
            $translations = $this->language->loadTranslation($job['data']['check_file']);
            if (isset($translations[$string])) {
                return true;
            }
        }

        // Check the string already written
        $handle = fopen($job['data']['file'], 'r');

        $found = false;
        while (($data = fgetcsv($handle, 1000)) !== false) {
            if (isset($data[0]) && $data[0] === $string) {
                $found = true;
                break;
            }
        }

        fclose($handle);
        return $found;
    }

}

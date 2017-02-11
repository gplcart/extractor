<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\handlers;

use gplcart\modules\extractor\models\Extract as ExtractorExtractModel;

/**
 * String extractor handler
 */
class Extract
{

    /**
     * Extract model instance
     * @var \gplcart\modules\extractor\models\Extract $extract
     */
    protected $extract;

    /**
     * Constructor
     * @param ExtractorExtractModel $extract
     */
    public function __construct(ExtractorExtractModel $extract)
    {
        $this->extract = $extract;
    }

    /**
     * Processes one extration job iteration
     * @param array $job
     */
    public function process(array &$job)
    {
        if (!isset($job['context']['offset'])) {
            $job['context']['offset'] = 0;
        }

        $scanned = $this->extract->scan(array('directory' => $job['data']['directory']));
        $files = array_slice($scanned, $job['context']['offset'], $job['data']['limit']);

        if (empty($files)) {
            $job['status'] = false;
            $job['done'] = $job['total'];
            return null;
        }

        foreach ($files as $file) {
            foreach ($this->extract->extractFromFile($file) as $string) {
                if (!$this->exists($string, $job)) {
                    $job['inserted'] ++;
                    gplcart_file_csv($job['data']['file'], array($string, ''));
                }
            }
        }

        $job['context']['offset'] += count($files);
        $job['done'] = $job['context']['offset'];
    }

    /**
     * Check if the string already exists in the file
     * @param string $string
     * @param array $job
     * @return boolean
     */
    protected function exists($string, array $job)
    {
        $handle = fopen($job['data']['file'], 'r');

        while (($data = fgetcsv($handle, 1000)) !== false) {
            if ($data[0] === $string) {
                return true;
            }
        }

        fclose($handle);
        return false;
    }

}

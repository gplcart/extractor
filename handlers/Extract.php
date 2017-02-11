<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\handlers;

use gplcart\modules\extractor\models\Extract as ExtractorExtractModel;
use gplcart\core\handlers\job\export\Base as BaseHandler;

/**
 * String extractor handler
 */
class Extract extends BaseHandler
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
        parent::__construct();

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
            $extracted = $this->extract->extractFromFile($file);
            foreach ($extracted as $string) {
                gplcart_file_csv($job['data']['file'], array($string, ''));
            }
            $job['inserted'] += count($extracted);
        }

        $job['context']['offset'] += count($files);
        $job['done'] = $job['context']['offset'];
    }

}

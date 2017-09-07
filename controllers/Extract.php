<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\controllers;

use gplcart\modules\extractor\models\Extract as ExtractorExtractModel;
use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to string extraction
 */
class Extract extends BackendController
{

    /**
     * Extractor's model instance
     * @var \gplcart\modules\extractor\models\Extract $extract
     */
    protected $extract;

    /**
     * @param ExtractorExtractModel $extract
     */
    public function __construct(ExtractorExtractModel $extract)
    {
        parent::__construct();

        $this->extract = $extract;
    }

    /**
     * Displays the extractor page
     */
    public function editExtract()
    {
        $this->downloadExtract();

        $this->setTitleEditExtract();
        $this->setBreadcrumbEditExtract();

        $this->setData('patterns', $this->extract->getPattern());
        $this->setData('directories', $this->getRelativeDirectories());

        $this->submitExtract();
        $this->outputEditExtract();
    }

    /**
     * Returns an array of relative directories to scan
     * @return array
     */
    protected function getRelativeDirectories()
    {
        $directories = array();
        foreach ($this->extract->getScannedDirectories() as $directory) {
            $directories[] = gplcart_relative_path($directory);
        }

        return $directories;
    }

    /**
     * Downloads a file with extracted strings
     */
    protected function downloadExtract()
    {
        $download = $this->getQuery('download');

        if (!empty($download)) {
            $this->download(gplcart_string_decode($download));
        }
    }

    /**
     * Sets title on the extractor page
     */
    protected function setTitleEditExtract()
    {
        $this->setTitle($this->text('Extractor'));
    }

    /**
     * Sets breadcrumbs on the extractor page
     */
    protected function setBreadcrumbEditExtract()
    {
        $this->setBreadcrumbHome();
    }

    /**
     * Handles submitted actions related to string extraction
     */
    protected function submitExtract()
    {
        if ($this->isPosted('extract')) {
            $file = $this->getFileExtract();

            if (empty($file)) {
                $this->redirect('', $this->text('Failed to create file'), 'warning');
            }

            $this->setJobExtract($file);
        }
    }

    /**
     * Creates a CSV file to write extracted string to and returns its path
     * @return string
     */
    protected function getFileExtract()
    {
        $file = gplcart_file_unique(GC_PRIVATE_TEMP_DIR . '/extracted-translations.csv');
        return file_put_contents($file, '') === false ? '' : $file;
    }

    /**
     * Renders and outputs the extractor page
     */
    protected function outputEditExtract()
    {
        $this->output('extractor|extract');
    }

    /**
     * Returns a total number of files to scan
     * @return integer
     */
    protected function getTotalExtract()
    {
        $options = array(
            'count' => true,
            'directory' => $this->extract->getScannedDirectories()
        );

        return (int) $this->extract->scan($options);
    }

    /**
     * Sets and performs string extraction job
     * @param string $file
     */
    protected function setJobExtract($file)
    {
        $limit = 10;

        $vars = array('@url' => $this->url('', array('download' => gplcart_string_encode($file))));
        $finish = $this->text('Extracted %inserted strings from %total files. <a href="@url">Download</a>', $vars);

        $job = array(
            'id' => 'extract',
            'data' => array(
                'file' => $file,
                'limit' => $limit,
                'directory' => $this->extract->getScannedDirectories()
            ),
            'total' => $this->getTotalExtract(),
            'redirect_message' => array('finish' => $finish)
        );

        $this->job->submit($job);
    }

}

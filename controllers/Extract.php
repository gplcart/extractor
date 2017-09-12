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

        $this->setData('scopes', $this->getScopesExtract());
        $this->setData('patterns', $this->extract->getPattern());

        $this->submitExtract();
        $this->outputEditExtract();
    }

    /**
     * Returns an array of scopes to extract from
     * @return array
     */
    protected function getScopesExtract()
    {
        $scopes = array(
            array(
                'name' => $this->text('Core'),
                'directories' => $this->extract->getScannedDirectories()
            )
        );

        foreach ($this->config->getModules() as $module) {
            $scopes[$module['module_id']] = array(
                'name' => $module['name'],
                'directories' => array($module['directory'])
            );
        }

        return $scopes;
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
        if ($this->isPosted('extract') && $this->validateExtract()) {
            $this->setJobExtract();
        }
    }

    /**
     * Validates submitted data
     */
    protected function validateExtract()
    {
        $this->setSubmitted('settings');
        $scope = $this->getSubmitted('scope');
        $scopes = $this->getScopesExtract();

        if (empty($scopes[$scope]['directories'])) {
            $this->setError('scope', $this->text('@field has invalid value', array('@field' => $this->text('Scope'))));
        } else {
            $this->setSubmitted('directories', $scopes[$scope]['directories']);
            $this->setSubmitted('file', $this->getFileExtract());
        }

        return !$this->hasErrors();
    }

    /**
     * Creates a CSV file to write extracted string to and returns its path
     * @return string
     */
    protected function getFileExtract()
    {
        $file = gplcart_file_unique(GC_PRIVATE_TEMP_DIR . '/extracted-translations.csv');
        file_put_contents($file, '');
        return $file;
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
     * @param array $directories
     * @return integer
     */
    protected function getTotalExtract(array $directories)
    {
        $options = array(
            'count' => true,
            'directory' => $directories
        );

        return (int) $this->extract->scan($options);
    }

    /**
     * Sets and performs string extraction job
     */
    protected function setJobExtract()
    {
        $limit = 10;
        $file = $this->getSubmitted('file');
        $directories = $this->getSubmitted('directories');
        $total = $this->getTotalExtract($directories);

        $vars = array('@url' => $this->url('', array('download' => gplcart_string_encode($file))));
        $finish = $this->text('Extracted %inserted strings from %total files. <a href="@url">Download</a>', $vars);

        $job = array(
            'id' => 'extract',
            'data' => array(
                'file' => $file,
                'limit' => $limit,
                'directory' => $directories
            ),
            'total' => $total,
            'redirect_message' => array('finish' => $finish)
        );

        $this->job->submit($job);
    }

}

<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor\controllers;

use gplcart\modules\extractor\models\Extractor as ExtractorModel;
use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to string extraction
 */
class Extractor extends BackendController
{

    /**
     * Extractor's model instance
     * @var \gplcart\modules\extractor\models\Extractor $extractor
     */
    protected $extractor;

    /**
     * @param ExtractorModel $extract
     */
    public function __construct(ExtractorModel $extract)
    {
        parent::__construct();

        $this->extractor = $extract;
    }

    /**
     * Displays the extractor page
     */
    public function editExtractor()
    {
        $this->downloadExtractor();

        $this->setTitleEditExtractor();
        $this->setBreadcrumbEditExtractor();

        $this->setData('patterns', $this->extractor->get());
        $this->setData('scopes', $this->getScopesExtractor());
        $this->setData('files', $this->getCoreTranslationsExtractor());

        $this->submitExtractor();
        $this->outputEditExtractor();
    }

    /**
     * Returns an array of core translation files
     * @return array
     */
    protected function getCoreTranslationsExtractor()
    {
        $files = array();
        foreach (array_keys($this->language->getList()) as $langcode) {
            $file = $this->translation->getFile($langcode);
            if (is_file($file)) {
                $files[basename($file)] = $file;
            }
        }

        return $files;
    }

    /**
     * Returns an array of scopes to extract from
     * @return array
     */
    protected function getScopesExtractor()
    {
        $scopes = array();
        foreach ($this->module->getList() as $module) {
            $scopes[$module['module_id']] = array(
                'name' => $module['name'],
                'directories' => array($module['directory'])
            );
        }

        gplcart_array_sort($scopes, 'name');

        $core = array(
            'name' => $this->text('Core'),
            'directories' => $this->extractor->getScannedDirectories()
        );

        array_unshift($scopes, $core);
        return $scopes;
    }

    /**
     * Downloads a file with extracted strings
     */
    protected function downloadExtractor()
    {
        $download = $this->getQuery('download');

        if (!empty($download)) {
            $this->download(gplcart_string_decode($download));
        }
    }

    /**
     * Sets title on the extractor page
     */
    protected function setTitleEditExtractor()
    {
        $this->setTitle($this->text('Extractor'));
    }

    /**
     * Sets breadcrumbs on the extractor page
     */
    protected function setBreadcrumbEditExtractor()
    {
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Handles submitted actions related to string extraction
     */
    protected function submitExtractor()
    {
        if ($this->isPosted('extract') && $this->validateExtractor()) {
            $this->setJobExtractor();
        }
    }

    /**
     * Validates submitted data
     */
    protected function validateExtractor()
    {
        $this->setSubmitted('settings');
        $scope = $this->getSubmitted('scope');
        $check = $this->getSubmitted('check_duplicates');

        $scopes = $this->getScopesExtractor();
        $core_files = $this->getCoreTranslationsExtractor();

        if (empty($scopes[$scope]['directories'])) {
            $this->setError('scope', $this->text('@field has invalid value', array('@field' => $this->text('Scope'))));
        } else {
            $this->setSubmitted('directories', $scopes[$scope]['directories']);
        }

        if (!empty($check) && !empty($scope)) {
            if (empty($core_files[$check])) {
                $this->setError('check_duplicates', $this->text('@field has invalid value', array('@field' => $this->text('Check duplicates'))));
            } else {
                $this->setSubmitted('check_file', $core_files[$check]);
            }
        }

        if (!$this->isError()) {
            $this->setSubmitted('file', $this->getFileExtractor());
        }

        return !$this->hasErrors();
    }

    /**
     * Creates a CSV file to write extracted string to and returns its path
     * @return string
     */
    protected function getFileExtractor()
    {
        $file = gplcart_file_private_temp('extracted-translations.csv', true);
        file_put_contents($file, '');
        return $file;
    }

    /**
     * Renders and outputs the extractor page
     */
    protected function outputEditExtractor()
    {
        $this->output('extractor|extract');
    }

    /**
     * Returns a total number of files to scan
     * @param array $directories
     * @return integer
     */
    protected function getTotalExtractor(array $directories)
    {
        $options = array(
            'count' => true,
            'directory' => $directories
        );

        return (int) $this->extractor->scan($options);
    }

    /**
     * Sets and performs string extraction job
     */
    protected function setJobExtractor()
    {
        $this->controlAccess('module_extractor_edit');

        $limit = 10;
        $file = $this->getSubmitted('file');
        $directories = $this->getSubmitted('directories');
        $total = $this->getTotalExtractor($directories);

        $vars = array('@url' => $this->url('', array('download' => gplcart_string_encode($file))));
        $finish_message = $this->text('Extracted %inserted strings from %total files. <a href="@url">Download</a>', $vars);
        $noresults_message = $this->text('Processed %total files. Nothing was extracted!');

        $job = array(
            'id' => 'extract',
            'data' => array(
                'file' => $file,
                'limit' => $limit,
                'directory' => $directories,
                'check_file' => $this->getSubmitted('check_file')
            ),
            'total' => $total,
            'redirect_message' => array('finish' => $finish_message, 'no_results' => $noresults_message)
        );

        $this->job->submit($job);
    }

}

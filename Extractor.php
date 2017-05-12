<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor;

use gplcart\core\Module;

/**
 * Main class for Extractor module
 */
class Extractor extends Module
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/tool/extract'] = array(
            'menu' => array('admin' => 'Extractor'),
            'handlers' => array(
                'controller' => array('gplcart\\modules\\extractor\\controllers\\Extract', 'editExtract')
            )
        );
    }

    /**
     * Implements hook "job.handlers"
     * @param array $handlers
     */
    public function hookJobHandlers(array &$handlers)
    {
        $handlers['extract'] = array(
            'handlers' => array(
                'process' => array('gplcart\\modules\\extractor\\handlers\\Extract', 'process')
            ),
        );
    }

    /**
     * Implements hook "cron"
     */
    public function hookCron()
    {
        // Automatically delete created files older than 1 day
        $lifespan = 86400;
        $directory = GC_PRIVATE_DOWNLOAD_DIR . '/extracted-translations';
        if (is_dir($directory)) {
            gplcart_file_delete($directory, array('csv'), $lifespan);
        }
    }

}

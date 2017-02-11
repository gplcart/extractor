<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor;

/**
 * Main class for Extractor module
 */
class Extractor
{

    /**
     * Module info
     * @return array
     */
    public function info()
    {
        return array(
            'name' => 'Extractor',
            'version' => '1.0.0-alfa.1',
            'description' => 'Allows to scan source files and extract translatable strings',
            'author' => 'Iurii Makukh',
            'core' => '1.x'
        );
    }

    /**
     * Implements hook "routes"
     * @param array $routes
     */
    public function hookRoute(array &$routes)
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

}

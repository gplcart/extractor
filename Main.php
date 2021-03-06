<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor;

/**
 * Main class for Extractor module
 */
class Main
{

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/tool/extract'] = array(
            'access' => 'module_extractor_edit',
            'menu' => array(
                'admin' => 'Extractor' // @text
            ),
            'handlers' => array(
                'controller' => array('gplcart\\modules\\extractor\\controllers\\Extractor', 'editExtractor')
            )
        );
    }

    /**
     * Implements hook "user.role.permissions"
     * @param array $permissions
     */
    public function hookUserRolePermissions(array &$permissions)
    {
        $permissions['module_extractor_edit'] = 'Extractor: edit'; // @text
    }

    /**
     * Implements hook "job.handlers"
     * @param array $handlers
     */
    public function hookJobHandlers(array &$handlers)
    {
        $handlers['extract'] = array(
            'handlers' => array(
                'process' => array('gplcart\\modules\\extractor\\handlers\\Extractor', 'process')
            ),
        );
    }

}

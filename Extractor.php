<?php

/**
 * @package Extractor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\extractor;

use gplcart\core\Module,
    gplcart\core\Config;

/**
 * Main class for Extractor module
 */
class Extractor extends Module
{

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
    }

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/tool/extract'] = array(
            'access' => 'module_extractor_edit',
            'menu' => array('admin' => /* @text */'Extractor'),
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
        $permissions['module_extractor_edit'] = /* @text */'Extractor: edit';
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

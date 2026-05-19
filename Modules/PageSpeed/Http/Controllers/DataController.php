<?php

namespace Modules\PageSpeed\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'pagespeed.access',
                'label' => __('pagespeed::lang.access_pagespeed'),
                'default' => false,
            ],
            [
                'value' => 'pagespeed.manage_settings',
                'label' => __('pagespeed::lang.manage_settings'),
                'default' => false,
            ],
            [
                'value' => 'pagespeed.clear_cache',
                'label' => __('pagespeed::lang.clear_cache'),
                'default' => false,
            ],
            [
                'value' => 'pagespeed.view_statistics',
                'label' => __('pagespeed::lang.view_statistics'),
                'default' => false,
            ],
        ];
    }

    /**
     * Superadmin package permissions
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'pagespeed_module',
                'label' => __('pagespeed::lang.pagespeed_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds PageSpeed menus
     *
     * @return void
     */
    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();

        $business_id = session()->get('user.business_id');
        $is_pagespeed_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'pagespeed_module');

        if ($is_pagespeed_enabled && auth()->user()->can('pagespeed.access')) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action([\Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'index']),
                    __('pagespeed::lang.pagespeed'),
                    [
                        'icon' => 'fas fa-rocket',
                        'active' => request()->segment(1) == 'pagespeed',
                        'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'
                    ]
                )->order(45);
            });
        }
    }

    /**
     * Parses notification message from database.
     *
     * @return array
     */
    public function parse_notification($notification)
    {
        $notification_data = [];
        // Can be used for future notifications from PageSpeed module
        return $notification_data;
    }
}

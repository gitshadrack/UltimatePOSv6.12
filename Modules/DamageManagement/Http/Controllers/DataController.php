<?php
/**
 * Damage Management Module
 * 
 * Comprehensive Damage Management Module - Track damaged products, manage dispatches 
 * to suppliers, and handle compensation claims with full documentation and reporting.
 * 
 * Module: DamageManagement
 * Author: Hackermiind
 * Version: 1.0.0
 * 
 * This is a complete free module for non commercial use.
 * 
 * @package Modules\DamageManagement
 */

namespace Modules\DamageManagement\Http\Controllers;

use Illuminate\Routing\Controller;

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
                'value' => 'damage_record.view',
                'label' => __('damagemanagement::damage.view_damage_records'),
                'default' => false,
            ],
            [
                'value' => 'damage_record.create',
                'label' => __('damagemanagement::damage.create_damage_records'),
                'default' => false,
            ],
            [
                'value' => 'damage_record.update',
                'label' => __('damagemanagement::damage.edit_damage_records'),
                'default' => false,
            ],
            [
                'value' => 'damage_record.delete',
                'label' => __('damagemanagement::damage.delete_damage_records'),
                'default' => false,
            ],
            [
                'value' => 'damage_dispatch.view',
                'label' => __('damagemanagement::damage.view_damage_dispatches'),
                'default' => false,
            ],
            [
                'value' => 'damage_dispatch.create',
                'label' => __('damagemanagement::damage.create_damage_dispatches'),
                'default' => false,
            ],
            [
                'value' => 'damage_dispatch.update',
                'label' => __('damagemanagement::damage.edit_damage_dispatches'),
                'default' => false,
            ],
            [
                'value' => 'damage_dispatch.delete',
                'label' => __('damagemanagement::damage.delete_damage_dispatches'),
                'default' => false,
            ],
        ];
    }

    /**
     * Defines module as a superadmin package.
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'damage_management_module',
                'label' => __('damagemanagement::damage.damage_management_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Modify admin menu to add Damage Management menu items.
     *
     * @return void
     */
    public function modifyAdminMenu()
    {
        try {
            // Check if module is actually installed (has version in system table)
            $is_installed = \App\System::getProperty('damagemanagement_version');
            if (empty($is_installed)) {
                return; // Module not installed, skip menu modification
            }

            $business_id = session()->get('user.business_id');
            $module_util = new \App\Utils\ModuleUtil();

            $is_damage_management_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'damage_management_module', 'superadmin_package');

            if ($is_damage_management_enabled && (auth()->user()->can('damage_record.view') || auth()->user()->can('damage_dispatch.view'))) {
                \Menu::modify('admin-sidebar-menu', function ($menu) {
                    $menu->dropdown(
                        __('damagemanagement::damage.damage_management'),
                        function ($sub) {
                            if (auth()->user()->can('damage_record.view')) {
                                $sub->url(
                                    action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'dashboard']),
                                    __('damagemanagement::damage.dashboard'),
                                    ['icon' => 'fa fa-bar-chart']
                                );
                                $sub->url(
                                    action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index']),
                                    __('damagemanagement::damage.records_list'),
                                    ['icon' => 'fa fa-list']
                                );
                                $sub->url(
                                    action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'stockReport']),
                                    __('damagemanagement::damage.stock_report'),
                                    ['icon' => 'fa fa-file-text']
                                );
                            }

                            if (auth()->user()->can('damage_dispatch.view')) {
                                $sub->url(
                                    action([\Modules\DamageManagement\Http\Controllers\DamageDispatchController::class, 'index']),
                                    __('damagemanagement::damage.dispatches'),
                                    ['icon' => 'fa fa-truck']
                                );
                            }
                        },
                        [
                            'icon' => 'fa fa-exclamation-triangle',
                            'active' => in_array(request()->segment(1), ['damage-records', 'damage-dispatches']),
                        ]
                    )->order(90);
                });
            }
        } catch (\Exception $e) {
            // Silently fail if something goes wrong
            \Log::error('Error modifying admin menu for DamageManagement: '.$e->getMessage());
        }
    }
}

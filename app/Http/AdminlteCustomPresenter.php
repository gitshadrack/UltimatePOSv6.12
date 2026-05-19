<?php

namespace App\Http;

use Nwidart\Menus\Presenters\Presenter;

class AdminlteCustomPresenter extends Presenter
{
    /**
     * {@inheritdoc}.
     */
    public function getOpenTagWrapper()
    {
        return '<div class="tw-flex-1 tw-p-3 tw-space-y-3 tw-overflow-y-auto tw-border-r tw-border-gray-200" id="side-bar">' . PHP_EOL;
    }

    /**
     * {@inheritdoc}.
     */
    public function getCloseTagWrapper()
    {
        return '</div>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}.
     */
    public function getMenuWithoutDropdownWrapper($item)
    {
        return '<a href="' . $item->getUrl() . '" title="" class="' . $this->getMenuClasses($item) . '"' . $this->getMenuStyleAttribute($item) . ' ' . $this->getItemAttributes($item) . '>' .
        $this->formatIcon($item->icon) . ' <span class="tw-truncate">' . $item->title . '</span>' .
            '</a>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}.
     */
    public function getActiveState($item, $state = ' tw-bg-blue-100 tw-text-blue-700')
    {
        return $item->isActive() ? $state : null;
    }

    /**
     * Get active state on child items.
     *
     * @param $item
     * @param  string  $state
     * @return null|string
     */
    public function getActiveStateOnChild($item, $state = 'tw-pb-1 tw-rounded-md tw-bg-blue-100 tw-text-blue-700')
    {
        return $item->hasActiveOnChild() ? $state : null;
    }

    /**
     * {@inheritdoc}.
     */
    public function getDividerWrapper()
    {
        // Assuming a divider is just a visual space in this design
        return '<div class="tw-my-2"></div>';
    }

    /**
     * {@inheritdoc}.
     */
    public function getHeaderWrapper($item)
    {
        return '<div class="tw-px-3 tw-py-2 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wider">' . $item->title . '</div>';
    }

    /**
     * {@inheritdoc}.
     */
    public function getMenuWithDropDownWrapper($item)
    {
        $dropdownToggle = '<a href="#" title="" class="' . $this->getMenuClasses($item, true) . '"' . $this->getMenuStyleAttribute($item) . ' ' . $this->getItemAttributes($item) . '>' .
        $this->formatIcon($item->icon) . ' <span class="tw-truncate">' . $item->title . '</span>' .
        '<svg aria-hidden="true" class="svg tw-ml-auto tw-size-4 tw-shrink-0' . $this->getDropdownArrowClasses($item) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' . $this->getArray($item) .
            '</svg>' .
            '</a>';

        $childItemsContainerStart = '';

        $childItemsContainerEnd = '';

        // Compile child menu items
        $childItems = $this->getChildMenuItems($item);

        // echo "here";
        // print_r($dropdownToggle);exit;

        return '<div class="' . $this->getActiveStateOnChild($item) . '">' . $dropdownToggle . $childItemsContainerStart . $childItems . $childItemsContainerEnd . '</div>' . PHP_EOL;
    }

    /**
     * Build top-level menu classes. Known module entries are rendered as colored pills.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @param  bool  $isDropdown
     * @return string
     */
    protected function getMenuClasses($item, $isDropdown = false)
    {
        $baseClasses = ($isDropdown ? 'drop_down ' : '') .
            'tw-flex tw-items-center tw-gap-3 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-tracking-tight tw-transition-all tw-duration-200 tw-rounded-lg tw-whitespace-nowrap';

        $colorClasses = $this->getMenuColorClasses($item);

        if (! empty($colorClasses)) {
            return $baseClasses . ' ' . $colorClasses;
        }

        $stateClasses = $isDropdown ? $this->getActiveStateOnChild($item) : $this->getActiveState($item);

        return $baseClasses . ' tw-text-gray-600 hover:tw-text-gray-900 hover:tw-bg-gray-100 focus:tw-text-gray-900 focus:tw-bg-gray-100' . $stateClasses;
    }

    /**
     * Module color classes matching the admin sidebar palette.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    protected function getMenuColorClasses($item)
    {
        $title = strtolower(trim(strip_tags($item->title)));

        $moduleColors = [
            'superadmin' => 'tw-text-white',
            'manufacturing' => 'tw-text-white',
            'repair' => 'tw-text-white',
            'accounting' => 'tw-text-white',
            'ai assistance' => 'tw-text-white',
            'hms' => 'tw-text-gray-900',
            'gym' => 'tw-text-white',
            'zatca' => 'tw-text-white',
        ];

        foreach ($moduleColors as $module => $classes) {
            if (strpos($title, $module) !== false) {
                return $classes;
            }
        }

        return '';
    }

    /**
     * Inline module colors so they work even when the CSS bundle has no matching utility.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    protected function getMenuStyleAttribute($item)
    {
        $title = strtolower(trim(strip_tags($item->title)));

        $moduleStyles = [
            'superadmin' => 'background-color: #12dddc; color: #ffffff;',
            'manufacturing' => 'background-color: #ff851b; color: #ffffff;',
            'repair' => 'background-color: #bc8f8f; color: #ffffff;',
            'accounting' => 'background-color: #c66bd8; color: #ffffff;',
            'ai assistance' => 'background-color: #6aa894; color: #ffffff;',
            'hms' => 'background-color: #fff200; color: #111827;',
            'gym' => 'background-color: #9ca3af; color: #ffffff;',
            'zatca' => 'background-color: #7c8ea3; color: #ffffff;',
        ];

        foreach ($moduleStyles as $module => $style) {
            if (strpos($title, $module) !== false) {
                return ' style="' . $style . '"';
            }
        }

        return '';
    }

    /**
     * Prevent duplicate inline style attributes on colored module rows.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    protected function getItemAttributes($item)
    {
        $attributes = $item->getAttributes();

        if (! empty($this->getMenuStyleAttribute($item))) {
            $attributes = preg_replace('/\sstyle=(["\']).*?\1/i', '', $attributes);
        }

        return $attributes;
    }

    /**
     * Keep dropdown arrows readable on colored module rows.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    protected function getDropdownArrowClasses($item)
    {
        return empty($this->getMenuColorClasses($item)) ? ' tw-text-gray-500' : ' tw-text-current';
    }

    /**
     * Get multi-level dropdown wrapper.
     *
     * Note: This example doesn't directly implement a multi-level dropdown, as it wasn't specified, but you could extend
     * the functionality similarly to `getMenuWithDropDownWrapper`, adjusting for deeper nesting.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    public function getMultiLevelDropdownWrapper($item)
    {
        // Placeholder for multi-level dropdown functionality if needed
        return '';
    }

    /**
     * Get child menu items.
     *
     * @param  \Nwidart\Menus\MenuItem  $item
     * @return string
     */
    public function getChildMenuItems($item)
    {

        $children = '';
        $displayStyle = $item->hasActiveOnChild() ? 'block' : 'none';

        


        if (count($item->getChilds()) > 0) {
            
            $children .= '<div class=" chiled tw-relative tw-mt-2 tw-mb-4 tw-pl-11" style="display:' . $displayStyle . '">
            <div class="tw-absolute tw-inset-y-0 tw-w-px tw-h-full tw-bg-gray-200 tw-left-5"></div>
            <div class="tw-space-y-3.5">';

            foreach ($item->getChilds() as $child) {

                $isActive = $child->isActive() ? 'tw-text-primary-700' : '';

                $children .= '<a href="' . $child->getUrl() . '" title="" class="tw-flex tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-truncate tw-transition-all tw-duration-200 hover:tw-text-gray-900 tw-whitespace-nowrap ' . $isActive . '"'.$isActive.' "' . $child->getAttributes() . '"' .$child->hasActiveOnChild() .'>' .
                $child->getIcon() . ' <span>' . $child->title . '</span>' .
                    '</a>' . PHP_EOL;
            }

            $children .= '</div></div>';
        }

        return $children;
    }

    /**
     * Returns the icon HTML. If the icon is SVG, it returns directly; otherwise, it assumes it's a FontAwesome class and wraps it in an <i> tag.
     *
     * @param string $icon
     * @return string
     */
    protected function formatIcon($icon)
    {
        // Check if the icon string contains "<svg", indicating it's an SVG icon
        if (strpos($icon, '<svg') !== false) {
            return $icon; // Return the SVG icon directly
        } else {
            // Assume it's a FontAwesome icon and return it wrapped in an <i> tag
            return '<i class="' . $icon . '"></i>';
        }
    }

    public function getArray($item)
    {
        if ($item->hasActiveOnChild()) {
            return '<path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M6 9l6 6l6 -6" />';
        } else {
            return '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M15 6l-6 6l6 6" />';
        }
    }
}



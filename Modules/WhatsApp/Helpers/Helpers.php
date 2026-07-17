<?php

if (! function_exists('country_code')) {
    /**
     * Change 0 to country code.
     *
     * @param int|string $value
     * @return string
     */
    function country_code(int|string $value): string
    {
        $ptn = '/^0/';
        $str = $value;
        $rpltxt = '91';
        $results = preg_replace($ptn, $rpltxt, $str);
        return $results;
    }
}

if (! function_exists('whatsapp_date')) {
    function whatsapp_date($value): string
    {
        return \Carbon\Carbon::parse($value)->isoFormat('D MMMM Y');
    }
}

if (! function_exists('whatsapp_date_human')) {
    function whatsapp_date_human($value): string
    {
        return \Carbon\Carbon::parse($value)->diffForHumans();
    }
}

if (! function_exists('whatsapp_date_local')) {
    function whatsapp_date_local($value): string
    {
        return \Carbon\Carbon::parse($value)->isoFormat('D MMMM Y');
    }
}

if (! function_exists('whatsapp_date_human_localize')) {
    function whatsapp_date_human_localize($value): string
    {
        return \Carbon\Carbon::parse($value)->diffForHumans();
    }
}
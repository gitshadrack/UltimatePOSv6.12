@extends('layouts.app')

@section('title', __('pagespeed::lang.pagespeed'))

@section('content')
<section class="content-header">
    <h1>@lang('pagespeed::lang.page_speed_optimization')
        <small><i class="fas fa-rocket"></i> @lang('pagespeed::lang.pagespeed_module')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget')
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fas fa-tachometer-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">@lang('pagespeed::lang.avg_load_time')</span>
                        <span class="info-box-number">{{ number_format($statistics['avg_load_time'] ?? 0, 2) }} ms</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">@lang('pagespeed::lang.cache_hits')</span>
                        <span class="info-box-number">{{ number_format($statistics['cache_hits'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fas fa-database"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">@lang('pagespeed::lang.cache_count')</span>
                        <span class="info-box-number">{{ number_format($statistics['cache_count'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="fas fa-chart-pie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">@lang('pagespeed::lang.hit_ratio')</span>
                        <span class="info-box-number">{{ number_format($statistics['hit_ratio'] ?? 0, 2) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        {!! Form::open(['url' => action([\Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'updateSettings']), 'method' => 'post', 'id' => 'pagespeed_settings_form']) !!}

        <div class="row">
            <div class="col-md-12">
                <!-- General Settings -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('pagespeed::lang.general_settings')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('enabled', 1, $settings->enabled ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_optimization')
                                    </label>
                                    <p class="help-block">@lang('pagespeed::lang.enable_optimization_help')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cache Settings -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-database"></i> @lang('pagespeed::lang.cache_settings')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('page_cache_enabled', 1, $settings->page_cache_enabled ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_page_cache')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('page_cache_lifetime', __('pagespeed::lang.page_cache_lifetime')) !!}
                                {!! Form::number('page_cache_lifetime', $settings->page_cache_lifetime ?? 86400, ['class' => 'form-control', 'min' => 60]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('cache_mobile_separate', 1, $settings->cache_mobile_separate ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.cache_mobile_separate')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('cache_logged_in_users', 1, $settings->cache_logged_in_users ?? false, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.cache_logged_in_users')
                                    </label>
                                    <p class="help-block text-warning">@lang('pagespeed::lang.warning_cache_logged_in')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HTML Optimization -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-code"></i> @lang('pagespeed::lang.html_optimization')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('minify_html', 1, $settings->minify_html ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.minify_html')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('remove_html_comments', 1, $settings->remove_html_comments ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.remove_html_comments')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CSS Optimization -->
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fab fa-css3-alt"></i> @lang('pagespeed::lang.css_optimization')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('minify_css', 1, $settings->minify_css ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.minify_css')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('combine_css', 1, $settings->combine_css ?? false, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.combine_css')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('inline_critical_css', 1, $settings->inline_critical_css ?? false, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.inline_critical_css')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JavaScript Optimization -->
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fab fa-js-square"></i> @lang('pagespeed::lang.js_optimization')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('minify_js', 1, $settings->minify_js ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.minify_js')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('combine_js', 1, $settings->combine_js ?? false, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.combine_js')
                                    </label>
                                    <p class="help-block text-warning">@lang('pagespeed::lang.warning_combine_files')</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('defer_js', 1, $settings->defer_js ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.defer_js')
                                    </label>
                                    <p class="help-block text-warning">@lang('pagespeed::lang.warning_minify')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Optimization -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-image"></i> @lang('pagespeed::lang.image_optimization')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('lazy_load_images', 1, $settings->lazy_load_images ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.lazy_load_images')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('lazy_load_iframes', 1, $settings->lazy_load_iframes ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.lazy_load_iframes')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('exclude_first_images', __('pagespeed::lang.exclude_first_images')) !!}
                                {!! Form::number('exclude_first_images', $settings->exclude_first_images ?? 3, ['class' => 'form-control', 'min' => 0]) !!}
                                <p class="help-block">@lang('pagespeed::lang.exclude_first_images_help')</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-cogs"></i> @lang('pagespeed::lang.advanced_settings')</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('gzip_enabled', 1, $settings->gzip_enabled ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_gzip')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('browser_cache_enabled', 1, $settings->browser_cache_enabled ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_browser_cache')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('dns_prefetch_enabled', 1, $settings->dns_prefetch_enabled ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_dns_prefetch')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('remove_query_strings', 1, $settings->remove_query_strings ?? true, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.remove_query_strings')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('cdn_enabled', 1, $settings->cdn_enabled ?? false, ['class' => 'input-icheck']) !!}
                                        @lang('pagespeed::lang.enable_cdn')
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                {!! Form::label('cdn_url', __('pagespeed::lang.cdn_url')) !!}
                                {!! Form::text('cdn_url', $settings->cdn_url ?? '', ['class' => 'form-control', 'placeholder' => 'https://cdn.example.com']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="box box-solid">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-save"></i> @lang('pagespeed::lang.save_settings')
                                </button>
                            </div>
                            <div class="col-md-6">
                                {!! Form::open(['url' => action([\Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'clearCache']), 'method' => 'post', 'style' => 'display:inline;']) !!}
                                <button type="submit" class="btn btn-danger btn-lg btn-block" onclick="return confirm('Are you sure you want to clear all cache?')">
                                    <i class="fas fa-trash"></i> @lang('pagespeed::lang.clear_all_cache')
                                </button>
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {!! Form::close() !!}
    @endcomponent
</section>
@endsection

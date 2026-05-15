@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Edit Business')

@section('content')
    @include('superadmin::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('messages.edit') {{ $business->name }}
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
                @lang('lang_v1.tenant_domain') / @lang('lang_v1.sign_in_page_image')
            </small>
        </h1>
    </section>

    <section class="content">
        <div
            class="tw-transition-all lg:tw-col-span-1 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw-translate-y-0.5 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                {!! Form::open([
                    'url' => action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'update'], [$business->id]),
                    'method' => 'put',
                    'files' => true,
                    'id' => 'superadmin_business_edit_form',
                ]) !!}

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('name', __('business.business_name') . ':*') !!}
                            {!! Form::text('name', $business->name, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('business.business_name'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('tenant_domain', __('lang_v1.tenant_domain') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-globe"></i>
                                </span>
                                {!! Form::text('tenant_domain', $business->tenant_domain, [
                                    'class' => 'form-control',
                                    'placeholder' => 'shop.co.ke',
                                ]) !!}
                            </div>
                            <p class="help-block"><i>@lang('lang_v1.tenant_domain_help')</i></p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('business_logo', __('business.upload_logo') . ':') !!}
                            {!! Form::file('business_logo', ['accept' => 'image/*']) !!}
                            <p class="help-block"><i>@lang('business.logo_help')</i></p>

                            @if (!empty($business->logo) && file_exists(public_path('uploads/business_logos/' . $business->logo)))
                                <div class="m-t-10">
                                    <img src="{{ asset('uploads/business_logos/' . $business->logo) }}"
                                        alt="@lang('business.upload_logo')" style="max-width: 180px; border-radius: 6px;">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('login_image', __('lang_v1.sign_in_page_image') . ':') !!}
                            {!! Form::file('login_image', ['accept' => 'image/*']) !!}
                            <p class="help-block"><i>@lang('lang_v1.sign_in_page_image_help')</i></p>

                            @if (!empty($business->login_image) && file_exists(public_path('uploads/business_login_images/' . $business->login_image)))
                                <div class="m-t-10">
                                    <img src="{{ asset('uploads/business_login_images/' . $business->login_image) }}"
                                        alt="@lang('lang_v1.sign_in_page_image')" style="max-width: 180px; border-radius: 6px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="tw-dw-btn tw-dw-btn-success tw-text-white">
                            @lang('messages.update')
                        </button>
                        <a href="{{ action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'show'], [$business->id]) }}"
                            class="tw-dw-btn tw-dw-btn-default">
                            @lang('messages.cancel')
                        </a>
                    </div>
                </div>

                {!! Form::close() !!}
            </div>
        </div>
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#business_logo, #login_image').fileinput({
                showUpload: false,
                showPreview: false,
                browseLabel: LANG.file_browse_label,
                removeLabel: LANG.remove
            });
        });
    </script>
@endsection

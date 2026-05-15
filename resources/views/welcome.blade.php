@extends('layouts.auth2')
@section('title', config('app.name', 'ultimatePOS'))
@inject('request', 'Illuminate\Http\Request')
@section('content')
@php
    $business_type_cards = [
        ['name' => 'Pharmacy', 'icon' => 'fa-medkit', 'copy' => 'Medicine stock, expiry tracking, and fast billing.'],
        ['name' => 'Electronics', 'icon' => 'fa-laptop', 'copy' => 'Serial numbers, warranties, repairs, and accessories.'],
        ['name' => 'Supermarket', 'icon' => 'fa-shopping-basket', 'copy' => 'Barcode sales, stock control, and daily cash flow.'],
        ['name' => 'Restaurant', 'icon' => 'fa-cutlery', 'copy' => 'Tables, kitchen orders, modifiers, and service flow.'],
        ['name' => 'Fashion', 'icon' => 'fa-tags', 'copy' => 'Sizes, colors, variants, and seasonal inventory.'],
        ['name' => 'Hardware', 'icon' => 'fa-wrench', 'copy' => 'Bulk items, suppliers, purchase orders, and margins.'],
    ];
@endphp

<div class="tw-w-full tw-max-w-7xl tw-mx-auto tw-flex tw-flex-col tw-gap-10 tw-pt-12 md:tw-pt-20 tw-pb-12">
    <section class="tw-text-center tw-flex tw-flex-col tw-items-center tw-gap-5">
        <div
            class="tw-inline-flex tw-items-center tw-justify-center tw-bg-white tw-rounded-full tw-p-1 tw-shadow-sm tw-ring-1 tw-ring-white/70">
            <img src="{{ asset('img/logo-small.png') }}" alt="{{ config('app.name', 'UltimatePOS') }}"
                class="tw-w-14 tw-h-14 tw-object-contain" />
        </div>

        <div class="tw-flex tw-flex-col tw-gap-3 tw-items-center">
            <h1 class="tw-text-4xl md:tw-text-6xl tw-font-extrabold tw-text-white tw-text-center">
                {{ config('app.name', 'UltimatePOS') }}
            </h1>

            @if (!empty(env('APP_TITLE', '')))
                <p class="tw-text-base md:tw-text-xl tw-font-medium tw-text-white/90 tw-text-center tw-max-w-3xl">
                    {{ env('APP_TITLE', '') }}
                </p>
            @endif
        </div>

        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-3 tw-items-center tw-justify-center">
            @if (config('constants.allow_registration'))
                <a href="{{ route('business.getRegister') }}@if(!empty(request()->lang)){{ '?lang='.request()->lang }}@endif"
                    class="tw-min-w-44 tw-h-12 tw-px-6 tw-rounded-full tw-bg-white tw-text-[#1f1f1f] tw-font-semibold tw-flex tw-items-center tw-justify-center hover:tw-bg-gray-100">
                    {{ __('business.register') }}
                </a>
            @endif

        </div>
    </section>

    <section>
        <div class="text-center" style="margin-bottom: 20px;">
            <h2 class="tw-text-2xl md:tw-text-3xl tw-font-bold tw-text-white">
                Choose the setup that fits your business
            </h2>
            <p class="tw-text-sm md:tw-text-base tw-text-white/90">
                Start with a POS flow shaped around the way your store sells.
            </p>
        </div>

        <div class="row">
            @foreach ($business_type_cards as $business_type)
                <div class="col-md-4 col-sm-6 col-xs-12">
                    <a href="{{ route('business.getRegister') }}@if(!empty(request()->lang)){{ '?lang='.request()->lang }}@endif"
                        class="business-type-card">
                        <div class="box box-solid">
                            <div class="box-body">
                                <div class="media">
                                    <div class="media-left">
                                        <span class="business-type-icon">
                                            <i class="fa {{ $business_type['icon'] }}"></i>
                                        </span>
                                    </div>
                                    <div class="media-body">
                                        <h4 class="media-heading">{{ $business_type['name'] }}</h4>
                                        <p>{{ $business_type['copy'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

</div>

@endsection

@section('javascript')
    <style>
        .business-type-card,
        .business-type-card:hover,
        .business-type-card:focus {
            color: inherit;
            display: block;
            text-decoration: none;
        }

        .business-type-card .box {
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            min-height: 128px;
            transition: all 0.2s ease;
        }

        .business-type-card:hover .box {
            border-color: #3c8dbc;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.14);
            transform: translateY(-2px);
        }

        .business-type-icon {
            align-items: center;
            background: #ecf0f5;
            border-radius: 50%;
            color: #3c8dbc;
            display: inline-flex;
            font-size: 22px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .business-type-card .media-heading {
            color: #1f1f1f;
            font-weight: 600;
            margin-top: 2px;
        }

        .business-type-card p {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 0;
        }
    </style>
@endsection

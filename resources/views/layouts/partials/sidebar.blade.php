<!-- Left side column. contains the logo and sidebar -->
<aside class="side-bar tw-relative tw-hidden tw-h-full tw-bg-gray-50 tw-w-64 xl:tw-w-64 lg:tw-flex lg:tw-flex-col tw-shrink-0">

    <!-- sidebar: style can be found in sidebar.less -->

    {{-- <a href="{{route('home')}}" class="logo">
		<span class="logo-lg">{{ Session::get('business.name') }}</span>
	</a> --}}

    <a href="{{route('home')}}"
        class="tw-flex tw-items-center tw-justify-center tw-w-full tw-border-r tw-h-15 tw-bg-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-800 tw-shrink-0 tw-border-primary-500/30">
        <p class="tw-text-lg tw-font-medium tw-text-white side-bar-heading tw-text-center">
            {{ Session::get('business.name') }} <span class="tw-inline-block tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full" title="Online"></span>
        </p>
    </a>

    <div class="tw-border-r tw-border-gray-200 tw-bg-white tw-p-3">
        <div class="tw-relative">
            <span class="tw-pointer-events-none tw-absolute tw-text-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-700"
                style="left: 12px; top: 50%; transform: translateY(-50%); line-height: 0;">
                <svg class="tw-size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                    <path d="M21 21l-6 -6" />
                </svg>
            </span>
            <input type="search"
                id="admin-sidebar-search"
                class="tw-w-full tw-rounded-lg tw-border tw-border-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-200 tw-bg-gray-50 tw-py-2 tw-pl-9 tw-pr-9 tw-text-sm tw-font-medium tw-text-gray-700 tw-outline-none tw-transition focus:tw-border-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-500 focus:tw-bg-white focus:tw-ring-2 focus:tw-ring-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-100"
                style="height: 34px; padding-left: 38px; padding-right: 38px;"
                placeholder="Search menu..." autocomplete="off" data-sidebar-search-input>
            <button type="button"
                class="tw-absolute tw-inline-flex tw-size-6 tw-items-center tw-justify-center tw-rounded-full tw-text-gray-400 tw-transition hover:tw-bg-gray-200 hover:tw-text-gray-700"
                style="display: none; right: 8px; top: 50%; transform: translateY(-50%); line-height: 0;" data-sidebar-search-clear aria-label="Clear search">
                <svg class="tw-size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M18 6l-12 12" />
                    <path d="M6 6l12 12" />
                </svg>
            </button>
        </div>
        <p class="tw-mt-2 tw-px-1 tw-text-xs tw-font-medium tw-text-gray-500" style="display: none;" data-sidebar-search-empty>
            No menu items found
        </p>
    </div>

    <!-- Sidebar Menu -->
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom') !!}

    <!-- /.sidebar-menu -->
    <!-- /.sidebar -->
</aside>

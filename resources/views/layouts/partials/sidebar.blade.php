<!-- Left side column. contains the logo and sidebar -->
<style>
    .upos-decorated-sidebar{background:#fff!important;box-shadow:1px 0 0 rgba(148,163,184,.18)}
    .upos-decorated-sidebar .upos-sidebar-brand{display:none!important}
    .upos-sidebar-search-wrap{padding:10px 7px 2px;border-right:1px solid #e5e7eb}
    .upos-sidebar-search{position:relative}
    .upos-sidebar-search-icon{position:absolute;top:50%;left:14px;width:15px;height:15px;color:#9aa6b7;transform:translateY(-50%);pointer-events:none}
    .upos-sidebar-search input{width:100%;height:34px;padding:7px 12px 7px 37px;font-size:13px;color:#344054;background:#eef3f8;border:1px solid #d8e1ea;border-radius:8px;outline:none;transition:border-color .2s ease,box-shadow .2s ease,background-color .2s ease}
    .upos-sidebar-search input::placeholder{color:#8492a6}
    .upos-sidebar-search input:focus{background:#fff;border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.16)}
    .upos-sidebar-menu{padding:8px 7px 14px!important;border-right-color:#e5e7eb!important}
    .upos-sidebar-menu.tw-space-y-3>:not([hidden])~:not([hidden]){margin-top:4px!important}
    .upos-sidebar-item{border-radius:7px}
    .upos-sidebar-link{min-height:32px;border-radius:7px!important;color:#334155!important;background:transparent}
    .upos-sidebar-link:hover,.upos-sidebar-link:focus{color:#1d4ed8!important;background:#eef5ff!important}
    .upos-sidebar-link.tw-bg-gray-200,.upos-sidebar-dropdown.tw-bg-gray-200>.upos-sidebar-link{color:#1d4ed8!important;background:#dfe9fb!important}
    .upos-sidebar-icon{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;color:#9aa6b7;flex:0 0 18px}
    .upos-sidebar-icon svg,.upos-sidebar-icon i{width:17px;height:17px;font-size:15px;color:currentColor;stroke:currentColor}
    .upos-sidebar-link:hover .upos-sidebar-icon,.upos-sidebar-link:focus .upos-sidebar-icon,.upos-sidebar-link.tw-bg-gray-200 .upos-sidebar-icon,.upos-sidebar-dropdown.tw-bg-gray-200>.upos-sidebar-link .upos-sidebar-icon{color:#2563eb}
    .upos-sidebar-link .svg{color:#9aa6b7!important}
    .upos-sidebar-pill-teal,.upos-sidebar-pill-orange,.upos-sidebar-pill-rose,.upos-sidebar-pill-purple,.upos-sidebar-pill-green,.upos-sidebar-pill-yellow,.upos-sidebar-pill-gray{color:#fff!important}
    .upos-sidebar-pill-teal,.upos-sidebar-pill-teal:hover,.upos-sidebar-pill-teal:focus{background:#20cfd0!important}
    .upos-sidebar-pill-orange,.upos-sidebar-pill-orange:hover,.upos-sidebar-pill-orange:focus{background:#ff811a!important}
    .upos-sidebar-pill-rose,.upos-sidebar-pill-rose:hover,.upos-sidebar-pill-rose:focus{background:#bd8d8f!important}
    .upos-sidebar-pill-purple,.upos-sidebar-pill-purple:hover,.upos-sidebar-pill-purple:focus{background:#c96bd5!important}
    .upos-sidebar-pill-green,.upos-sidebar-pill-green:hover,.upos-sidebar-pill-green:focus{background:#74a692!important}
    .upos-sidebar-pill-yellow,.upos-sidebar-pill-yellow:hover,.upos-sidebar-pill-yellow:focus{color:#344054!important;background:#f2f000!important}
    .upos-sidebar-pill-gray,.upos-sidebar-pill-gray:hover,.upos-sidebar-pill-gray:focus{color:#3f4854!important;background:#a9a9a9!important}
    .upos-sidebar-pill-teal .upos-sidebar-icon,.upos-sidebar-pill-orange .upos-sidebar-icon,.upos-sidebar-pill-rose .upos-sidebar-icon,.upos-sidebar-pill-purple .upos-sidebar-icon,.upos-sidebar-pill-green .upos-sidebar-icon,.upos-sidebar-pill-yellow .upos-sidebar-icon,.upos-sidebar-pill-gray .upos-sidebar-icon,.upos-sidebar-pill-teal .svg,.upos-sidebar-pill-orange .svg,.upos-sidebar-pill-rose .svg,.upos-sidebar-pill-purple .svg,.upos-sidebar-pill-green .svg,.upos-sidebar-pill-yellow .svg,.upos-sidebar-pill-gray .svg{color:currentColor!important}
    .upos-sidebar-dropdown .chiled{margin-top:6px!important;margin-bottom:8px!important}
    .upos-sidebar-dropdown .chiled a{color:#64748b!important}
    .upos-sidebar-dropdown .chiled a:hover{color:#1d4ed8!important}
</style>
<aside class="side-bar upos-decorated-sidebar tw-relative tw-hidden tw-h-full tw-bg-white tw-w-64 xl:tw-w-64 lg:tw-flex lg:tw-flex-col tw-shrink-0">

    <!-- sidebar: style can be found in sidebar.less -->

    {{-- <a href="{{route('home')}}" class="logo">
		<span class="logo-lg">{{ Session::get('business.name') }}</span>
	</a> --}}

    <a href="{{route('home')}}"
        class="upos-sidebar-brand tw-flex tw-items-center tw-justify-center tw-w-full tw-border-r tw-h-15 tw-bg-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-800 tw-shrink-0 tw-border-primary-500/30">
        <p class="tw-text-lg tw-font-medium tw-text-white side-bar-heading tw-text-center">
            {{ Session::get('business.name') }} <span class="tw-inline-block tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full" title="Online"></span>
        </p>
    </a>

    <div class="upos-sidebar-search-wrap">
        <div class="upos-sidebar-search">
            <svg aria-hidden="true" class="upos-sidebar-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/>
                <path d="M21 21l-6 -6"/>
            </svg>
            <input type="search" id="upos-sidebar-search-input" autocomplete="off" placeholder="@lang('lang_v1.search_menu')">
        </div>
    </div>

    <!-- Sidebar Menu -->
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom') !!}

    <!-- /.sidebar-menu -->
    <!-- /.sidebar -->
</aside>

<script>
    (function () {
        var searchInput = document.getElementById('upos-sidebar-search-input');
        var sidebar = document.getElementById('side-bar');

        if (!searchInput || !sidebar) {
            return;
        }

        searchInput.addEventListener('input', function () {
            var keyword = this.value.trim().toLowerCase();
            var items = sidebar.querySelectorAll('.upos-sidebar-item');

            items.forEach(function (item) {
                var text = item.textContent.toLowerCase();
                item.style.display = !keyword || text.indexOf(keyword) !== -1 ? '' : 'none';
            });
        });
    })();
</script>

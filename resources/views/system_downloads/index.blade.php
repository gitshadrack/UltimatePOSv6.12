@extends('layouts.app')
@section('title', __('lang_v1.system_downloads'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.system_downloads')
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-9 col-lg-8">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-5 tw-justify-between">
                    <div>
                        <h3 class="tw-mt-0 tw-font-bold">{{ $download['name'] }}</h3>
                        <p class="text-muted">
                            Install or update the local Windows agent used for receipt printing,
                            offline print retries, and supported cash-drawer commands.
                        </p>

                        @if ($download['available'])
                            <dl class="dl-horizontal tw-mb-4">
                                <dt>Version</dt>
                                <dd>{{ $download['version'] }}</dd>
                                <dt>File</dt>
                                <dd>{{ $download['file_name'] }}</dd>
                                <dt>Size</dt>
                                <dd>{{ $download['size'] }}</dd>
                                <dt>Updated</dt>
                                <dd>{{ \Carbon\Carbon::createFromTimestamp($download['modified_at'])->format('d M Y H:i') }}</dd>
                                <dt>SHA-256</dt>
                                <dd><code class="tw-break-all">{{ strtoupper($download['sha256']) }}</code></dd>
                            </dl>

                            <a href="{{ route('system-downloads.print-server') }}"
                               class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full">
                                <i class="fa fa-download"></i>
                                Download Print Server
                            </a>
                        @else
                            <div class="alert alert-warning">
                                The installer is not present on this ERP deployment. Use the repository
                                download or ask the system administrator to deploy the release artifact.
                            </div>
                        @endif

                        <a href="{{ $download['repository_url'] }}"
                           class="tw-dw-btn tw-dw-btn-outline tw-rounded-full"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="fa fa-github"></i>
                            Download from repository
                        </a>
                    </div>
                </div>

                <hr>

                <h4 class="tw-font-bold">Installation and updating</h4>
                <ol>
                    <li>Download the installer on the cashier computer.</li>
                    <li>Sign in to the Windows account normally used by that cashier.</li>
                    <li>Run <code>UltimatePOS-PrintServer-Setup.exe</code>.</li>
                    <li>When updating, run the newer installer directly; do not uninstall first.</li>
                    <li>Open <strong>Windows Start &gt; UltimatePOS &gt; Test Print Server</strong>.</li>
                    <li>Confirm the POS displays <strong>Printer ready</strong>, then print one test receipt.</li>
                </ol>

                <div class="alert alert-info tw-mb-0">
                    Pending receipt jobs are preserved during an in-place update. Uninstalling removes
                    the local pending queue, so verify that no genuine receipts are waiting before
                    uninstalling.
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

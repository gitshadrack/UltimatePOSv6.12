@extends('layouts.app')
@section('title', 'Tenant migration | ' . $business->name)

@section('content')
    @include('superadmin::layouts.nav')
    <section class="content-header">
        <h1>Tenant migration <small>{{ $business->name }} (ID {{ $business->id }})</small></h1>
    </section>
    <section class="content">
        @if(session('migration_status'))<div class="alert alert-success">{{ session('migration_status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Migration safety</h3></div>
            <div class="box-body">
                <p>This workflow moves one business to a fresh, schema-matched database. It does not merge into an existing business.</p>
                <p>For the final archive, put the source app in maintenance mode, stop queue workers and offline sync, and keep it offline until the destination is verified. The archive is AES-256 encrypted; keep its passphrase separate.</p>
                <p>Some legacy tables use MyISAM. If import fails, discard the destination database and start again with a clean one. Do not bring either site online until reconciliation is complete.</p>
                <table class="table table-bordered" style="max-width: 500px">
                    <thead><tr><th>Record type</th><th>Current source count</th></tr></thead>
                    <tbody>
                    @foreach($counts as $name => $count)
                        <tr><td>{{ ucfirst($name) }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                <hr>
                <h4>Export this business</h4>
                <p>The Export button works only after the app is in maintenance mode. Start maintenance with a bypass secret, visit that secret URL as a Superadmin, stop workers and offline sync, then return here.</p>
                <form method="POST" action="{{ route('superadmin.business.migration.export', $business->id) }}">
                    @csrf
                    <div class="form-group"><label for="passphrase">Archive passphrase (at least 12 characters)</label><input class="form-control" id="passphrase" type="password" name="passphrase" autocomplete="new-password" required></div>
                    <div class="form-group"><label for="passphrase_confirmation">Confirm passphrase</label><input class="form-control" id="passphrase_confirmation" type="password" name="passphrase_confirmation" autocomplete="new-password" required></div>
                    <label><input type="checkbox" name="writes_stopped" value="1" required> All offline POS sales have synced, and background writers are stopped.</label>
                    <div class="form-group"><button type="submit" class="btn btn-primary">Create encrypted export</button></div>
                </form>
                @if(count($archives))
                    <h4>Existing encrypted archives</h4>
                    @foreach($archives as $archive)
                        <p><a class="btn btn-default" href="{{ route('superadmin.business.migration.download', [$business->id, basename($archive)]) }}">Download {{ basename($archive) }}</a></p>
                    @endforeach
                @endif
                <hr>
                <p>From the source application directory, after stopping writes:</p>
                <pre>php artisan down --secret=YOUR_RANDOM_BYPASS_SECRET
php artisan pos:tenant-export {{ $business->id }}</pre>
                <p>Copy the resulting encrypted archive to the destination. On the destination, install the same app version and modules, migrate the schema, keep the app in maintenance mode, then run:</p>
                <pre>php artisan pos:tenant-import /path/to/archive.zip
php artisan pos:tenant-import /path/to/archive.zip --commit</pre>
                <p>The first import command is a dry run. Both commands prompt for the archive passphrase. Review the row counts and financial totals before changing DNS or enabling the destination site.</p>
                <p>The archive does not contain <code>.env</code> or <code>APP_KEY</code>. Review encrypted settings and payment integrations separately. See <code>docs/TENANT_MIGRATION.md</code> for the full checklist.</p>
            </div>
        </div>
    </section>
@endsection

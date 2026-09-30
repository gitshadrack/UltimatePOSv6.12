@extends('layouts.app')
@section('title', 'Tenant migration')

@section('content')
    @include('superadmin::layouts.nav')
    <section class="content-header"><h1>Tenant migration</h1></section>
    <section class="content">
        @if(session('migration_status'))<div class="alert alert-success">{{ session('migration_status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Export one business</h3></div>
            <div class="box-body">
                <p>Select a business to see its counts and create an encrypted export.</p>
                @forelse($businesses as $business)
                    <p><a class="btn btn-primary" href="{{ route('superadmin.business.migration', $business->id) }}">Export {{ $business->name }} (ID {{ $business->id }})</a></p>
                @empty
                    <p>No businesses exist in this database.</p>
                @endforelse
            </div>
        </div>

        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Import into this database</h3></div>
            <div class="box-body">
                <p>Import is allowed only when this database has no businesses and the app is in maintenance mode. Use the maintenance bypass link to access Superadmin. A standard installation with existing business or conflicting user IDs will be rejected.</p>
                @if($businesses->isNotEmpty())
                    <div class="alert alert-warning">This database is not empty. Import is disabled here; use a fresh destination database.</div>
                @else
                    <form method="POST" action="{{ route('superadmin.tenant-migration.import-check') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group"><label for="tenant_archive">Encrypted tenant archive</label><input class="form-control" id="tenant_archive" type="file" name="archive" required></div>
                        <div class="form-group"><label for="import_passphrase">Archive passphrase</label><input class="form-control" id="import_passphrase" type="password" name="passphrase" autocomplete="off" required></div>
                        <button type="submit" class="btn btn-warning">Check import archive</button>
                    </form>
                    <p class="help-block">Large archives may exceed the server upload limit. Use <code>php artisan pos:tenant-import</code> instead.</p>
                    @if(is_array($preview))
                        <hr>
                        <div class="alert alert-info">Preflight passed: business ID {{ $preview['business_id'] }}; {{ $preview['tables'] }} tables. Confirm the ID and use the same passphrase to import.</div>
                        <form method="POST" action="{{ route('superadmin.tenant-migration.import') }}">
                            @csrf
                            <div class="form-group"><label for="confirm_business_id">Type business ID {{ $preview['business_id'] }}</label><input class="form-control" id="confirm_business_id" type="number" name="confirm_business_id" required></div>
                            <div class="form-group"><label for="commit_passphrase">Archive passphrase</label><input class="form-control" id="commit_passphrase" type="password" name="passphrase" autocomplete="off" required></div>
                            <label><input type="checkbox" name="confirm_empty" value="1" required> I understand a failed import requires a new empty database.</label>
                            <div class="form-group"><button type="submit" class="btn btn-danger">Import business now</button></div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </section>
@endsection

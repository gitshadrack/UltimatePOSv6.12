<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TenantMigrationController extends Controller
{
    public function index()
    {
        $this->authorizeSuperadmin();

        $businesses = Business::orderBy('name')->get(['id', 'name']);
        $preview = session('tenant_import_preview');

        return view('superadmin::business.tenant_migration_index', compact('businesses', 'preview'));
    }

    public function show(int $id)
    {
        $this->authorizeSuperadmin();

        $business = Business::findOrFail($id);
        $counts = [
            'transactions' => DB::table('transactions')->where('business_id', $id)->count(),
            'payments' => DB::table('transaction_payments')->where('business_id', $id)->count(),
            'products' => DB::table('products')->where('business_id', $id)->count(),
            'users' => DB::table('users')->where('business_id', $id)->count(),
            'media' => DB::table('media')->where('business_id', $id)->count(),
        ];
        $archives = glob($this->archiveDirectory().'/business-'.$id.'-*.zip') ?: [];
        rsort($archives);

        return view('superadmin::business.tenant_migration', compact('business', 'counts', 'archives'));
    }

    public function export(Request $request, int $id, TenantMigrationService $migration)
    {
        $this->authorizeSuperadmin();
        $this->requireMaintenance();
        $request->validate([
            'passphrase' => 'required|string|min:12|confirmed',
            'writes_stopped' => 'accepted',
        ]);
        Business::findOrFail($id);
        $directory = $this->archiveDirectory();
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            return back()->withErrors(['archive' => 'Could not create private archive directory.']);
        }
        $filename = 'business-'.$id.'-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';
        $path = $directory.'/'.$filename;
        try {
            set_time_limit(0);
            $manifest = $migration->export($id, $path, $request->input('passphrase'));

            return redirect()->route('superadmin.business.migration', $id)
                ->with('migration_status', 'Encrypted export created: '.$filename.' ('.count($manifest['tables']).' tables). Download it below while keeping the source offline.');
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            }

            return back()->withErrors(['archive' => $e->getMessage()]);
        }
    }

    public function download(int $id, string $archive)
    {
        $this->authorizeSuperadmin();
        Business::findOrFail($id);
        if (! preg_match('/^business-'.preg_quote((string) $id, '/').'-\d{8}-\d{6}-[0-9a-f]{8}\.zip$/', $archive)) {
            abort(404);
        }
        $directory = realpath($this->archiveDirectory());
        $path = $directory ? realpath($directory.'/'.$archive) : false;
        if (! $path || dirname($path) !== $directory) {
            abort(404);
        }

        return response()->download($path, $archive, ['Cache-Control' => 'private, no-store']);
    }

    public function checkImport(Request $request, TenantMigrationService $migration)
    {
        $this->authorizeSuperadmin();
        $this->requireMaintenance();
        $request->validate([
            'archive' => 'required|file|max:524288',
            'passphrase' => 'required|string|min:12',
        ]);
        if (Business::exists()) {
            return back()->withErrors(['archive' => 'Import requires a fresh destination with no businesses.']);
        }
        $directory = $this->archiveDirectory().'/incoming';
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            return back()->withErrors(['archive' => 'Could not create private incoming directory.']);
        }
        $token = bin2hex(random_bytes(16));
        $path = $directory.'/'.$token.'.zip';
        $request->file('archive')->move($directory, $token.'.zip');
        try {
            set_time_limit(0);
            $result = $migration->import($path, $request->input('passphrase'));
            session(['tenant_import_preview' => [
                'token' => $token,
                'business_id' => $result['business_id'],
                'tables' => count($result['tables']),
            ]]);

            return redirect()->route('superadmin.tenant-migration.index')
                ->with('migration_status', 'Archive passed preflight. Review the business ID before importing.');
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            }

            return back()->withErrors(['archive' => $e->getMessage()]);
        }
    }

    public function import(Request $request, TenantMigrationService $migration)
    {
        $this->authorizeSuperadmin();
        $this->requireMaintenance();
        $request->validate([
            'passphrase' => 'required|string|min:12',
            'confirm_business_id' => 'required|integer',
            'confirm_empty' => 'accepted',
        ]);
        $preview = session('tenant_import_preview');
        if (! is_array($preview) || ! hash_equals((string) ($preview['business_id'] ?? ''), (string) $request->input('confirm_business_id')) ||
            ! preg_match('/^[0-9a-f]{32}$/', (string) ($preview['token'] ?? ''))) {
            return back()->withErrors(['archive' => 'Import preview expired or business ID does not match.']);
        }
        $path = $this->archiveDirectory().'/incoming/'.$preview['token'].'.zip';
        if (! is_file($path)) {
            return back()->withErrors(['archive' => 'Staged archive was not found. Upload it again.']);
        }
        try {
            set_time_limit(0);
            $migration->import($path, $request->input('passphrase'), true);
            session()->forget('tenant_import_preview');

            return redirect()->route('superadmin.tenant-migration.index')
                ->with('migration_status', 'Import completed. Reconcile transactions and payments before enabling the destination.');
        } catch (\Throwable $e) {
            return back()->withErrors(['archive' => $e->getMessage().' If the import started, discard this destination database and retry on a new empty one.']);
        }
    }

    private function authorizeSuperadmin(): void
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403);
        }
    }

    private function requireMaintenance(): void
    {
        if (! app()->isDownForMaintenance()) {
            abort(409, 'Enable maintenance mode with a bypass secret before exporting or importing.');
        }
    }

    private function archiveDirectory(): string
    {
        return storage_path('app/tenant-migrations');
    }
}

<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\DarajaPayment;
use App\IntaSendPayment;
use App\Utils\ModuleUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MpesaDashboardController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $providers = $this->enabledProviders($business_id);
        $this->authorizeDashboard($providers);

        $filters = $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if (! empty($filters['location_id'])) {
            BusinessLocation::where('business_id', $business_id)->findOrFail($filters['location_id']);
        }

        $metrics = $this->emptyMetrics();
        $provider_metrics = [];
        $metric_queries = [];
        $migration_required = false;

        if ($providers['intasend']) {
            if (Schema::hasTable('intasend_payments')) {
                $metric_queries['intasend'] = [
                    'query' => $this->filteredQuery(IntaSendPayment::query(), $business_id, $filters),
                    'has_reversal_status' => Schema::hasColumn('intasend_payments', 'reversal_status'),
                ];
            } else {
                $migration_required = true;
            }
        }

        if ($providers['daraja']) {
            if (Schema::hasTable('daraja_payments')) {
                $metric_queries['daraja'] = [
                    'query' => $this->filteredQuery(DarajaPayment::query(), $business_id, $filters),
                    'has_reversal_status' => Schema::hasColumn('daraja_payments', 'reversal_status'),
                ];
            } else {
                $migration_required = true;
            }
        }

        if (! empty($metric_queries)) {
            [$metrics, $provider_metrics] = $this->metricsForProviders($metric_queries);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('mpesa.dashboard', compact(
            'metrics',
            'provider_metrics',
            'providers',
            'business_locations',
            'filters',
            'migration_required'
        ));
    }

    public function records(Request $request, $metric)
    {
        $metric_titles = [
            'total_records' => 'mpesa_total_records',
            'linked_to_sales' => 'mpesa_linked_to_sales',
            'unlinked_ready' => 'mpesa_unlinked_ready',
            'picked' => 'mpesa_picked',
            'pending' => 'mpesa_pending',
            'failed_cancelled' => 'mpesa_failed_cancelled',
            'linked_amount' => 'mpesa_linked_amount',
            'reversal_pending_requested' => 'mpesa_reversal_pending_requested',
            'successful_reversal' => 'mpesa_successful_reversal',
        ];
        abort_unless(isset($metric_titles[$metric]), 404);

        $business_id = $request->session()->get('user.business_id');
        $providers = $this->enabledProviders($business_id);
        $this->authorizeDashboard($providers);
        $filters = $request->validate([
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        if (! empty($filters['location_id'])) {
            BusinessLocation::where('business_id', $business_id)->findOrFail($filters['location_id']);
        }

        $provider_queries = [];
        if ($providers['intasend'] && Schema::hasTable('intasend_payments')) {
            $provider_queries[] = $this->providerRecordsQuery('intasend_payments', 'IntaSend', $business_id);
        }
        if ($providers['daraja'] && Schema::hasTable('daraja_payments')) {
            $provider_queries[] = $this->providerRecordsQuery('daraja_payments', 'Safaricom M-PESA', $business_id);
        }
        abort_if(empty($provider_queries), 404);

        $union = array_shift($provider_queries);
        foreach ($provider_queries as $provider_query) {
            $union->unionAll($provider_query);
        }

        $records_query = DB::query()->fromSub($union, 'mpesa_records')
            ->leftJoin('business_locations as bl', 'mpesa_records.business_location_id', '=', 'bl.id')
            ->leftJoin('transaction_payments as tp', 'mpesa_records.transaction_payment_id', '=', 'tp.id')
            ->leftJoin('transactions as sale', 'tp.transaction_id', '=', 'sale.id')
            ->select([
                'mpesa_records.*',
                'bl.name as location_name',
                'tp.payment_ref_no',
                'sale.invoice_no',
            ]);

        $this->applyRecordFilters($records_query, $metric, $filters);
        $records = $records_query->orderByDesc('mpesa_records.created_at')->paginate(25)->appends($request->query());
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $metric_title = __('lang_v1.'.$metric_titles[$metric]);

        return view('mpesa.records', compact('records', 'metric', 'metric_title', 'filters', 'business_locations'));
    }

    protected function providerRecordsQuery($table_name, $provider, $business_id)
    {
        $reversal_status = Schema::hasColumn($table_name, 'reversal_status')
            ? 'reversal_status'
            : DB::raw(chr(39).'none'.chr(39).' as reversal_status');

        return DB::table($table_name)->where('business_id', $business_id)->select([
            'id',
            'business_location_id',
            'transaction_payment_id',
            'transaction_code',
            'phone_number',
            'amount',
            'status',
            'reconciliation_status',
            'is_attached',
            $reversal_status,
            'created_at',
            DB::raw(chr(39).$provider.chr(39).' as provider'),
        ]);
    }

    protected function applyRecordFilters($query, $metric, array $filters)
    {
        if (! empty($filters['location_id'])) {
            $query->where('mpesa_records.business_location_id', $filters['location_id']);
        }
        if (! empty($filters['start_date'])) {
            $query->whereDate('mpesa_records.created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('mpesa_records.created_at', '<=', $filters['end_date']);
        }

        if (in_array($metric, ['linked_to_sales', 'linked_amount'])) {
            $query->whereNotNull('mpesa_records.transaction_payment_id');
        } elseif ($metric === 'unlinked_ready') {
            $query->whereRaw('UPPER(mpesa_records.status) = ?', ['COMPLETE'])
                ->whereNull('mpesa_records.transaction_payment_id')
                ->where('mpesa_records.is_attached', 0)
                ->where(function ($query) {
                    $query->whereNull('mpesa_records.reconciliation_status')
                        ->orWhere('mpesa_records.reconciliation_status', '!=', 'ignored');
                });
        } elseif ($metric === 'picked') {
            $query->whereRaw('UPPER(mpesa_records.status) = ?', ['COMPLETE'])
                ->whereNull('mpesa_records.transaction_payment_id')
                ->where(function ($query) {
                    $query->where('mpesa_records.is_attached', 1)
                        ->orWhereIn('mpesa_records.reconciliation_status', ['matched', 'attached']);
                });
        } elseif ($metric === 'pending') {
            $query->whereRaw('UPPER(mpesa_records.status) = ?', ['PENDING']);
        } elseif ($metric === 'failed_cancelled') {
            $query->whereRaw(
                'UPPER(mpesa_records.status) IN (?, ?, ?, ?, ?, ?)',
                ['FAILED', 'CANCELLED', 'CANCELED', 'REJECTED', 'TIMEOUT', 'TIMED_OUT']
            );
        } elseif ($metric === 'reversal_pending_requested') {
            $query->whereIn('mpesa_records.reversal_status', ['requested', 'pending']);
        } elseif ($metric === 'successful_reversal') {
            $query->where('mpesa_records.reversal_status', 'successful');
        }
    }

    protected function filteredQuery(Builder $query, $business_id, array $filters)
    {
        $query->where('business_id', $business_id);

        if (! empty($filters['location_id'])) {
            $query->where('business_location_id', $filters['location_id']);
        }
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query;
    }

    protected function metricsForProviders(array $provider_queries)
    {
        $union = null;
        foreach ($provider_queries as $provider => $configuration) {
            $reversal_status = $configuration['has_reversal_status']
                ? 'reversal_status'
                : DB::raw(chr(39).'none'.chr(39).' as reversal_status');

            $source = $configuration['query']->select([
                'status',
                'transaction_payment_id',
                'is_attached',
                'reconciliation_status',
                'amount',
                $reversal_status,
                DB::raw(chr(39).$provider.chr(39).' as provider'),
            ]);

            if ($union === null) {
                $union = $source;
            } else {
                $union->unionAll($source);
            }
        }

        $selects = [
            'provider',
            'COUNT(*) as total_records',
            'SUM(CASE WHEN transaction_payment_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_sales',
            'SUM(CASE WHEN UPPER(status) = \'COMPLETE\' AND transaction_payment_id IS NULL AND is_attached = 0 AND (reconciliation_status IS NULL OR reconciliation_status != \'ignored\') THEN 1 ELSE 0 END) as unlinked_ready',
            'SUM(CASE WHEN UPPER(status) = \'COMPLETE\' AND transaction_payment_id IS NULL AND (is_attached = 1 OR reconciliation_status IN (\'matched\', \'attached\')) THEN 1 ELSE 0 END) as picked',
            'SUM(CASE WHEN UPPER(status) = \'PENDING\' THEN 1 ELSE 0 END) as pending',
            'SUM(CASE WHEN UPPER(status) IN (\'FAILED\', \'CANCELLED\', \'CANCELED\', \'REJECTED\', \'TIMEOUT\', \'TIMED_OUT\') THEN 1 ELSE 0 END) as failed_cancelled',
            'COALESCE(SUM(CASE WHEN transaction_payment_id IS NOT NULL THEN amount ELSE 0 END), 0) as linked_amount',
            'SUM(CASE WHEN reversal_status IN (\'requested\', \'pending\') THEN 1 ELSE 0 END) as reversal_pending_requested',
            'SUM(CASE WHEN reversal_status = \'successful\' THEN 1 ELSE 0 END) as successful_reversal',
            'COALESCE(SUM(CASE WHEN reversal_status = \'successful\' THEN amount ELSE 0 END), 0) as successful_reversal_amount',
        ];

        $rows = DB::query()->fromSub($union, 'mpesa_metrics')
            ->selectRaw(implode(', ', $selects))
            ->groupBy('provider')
            ->get();

        $metrics = $this->emptyMetrics();
        $provider_metrics = [];
        foreach ($rows as $row) {
            $provider_metrics[$row->provider] = $this->metricsFromRow($row);
            $metrics = $this->mergeMetrics($metrics, $provider_metrics[$row->provider]);
        }

        return [$metrics, $provider_metrics];
    }

    protected function metricsFromRow($row)
    {
        $metrics = $this->emptyMetrics();
        foreach (array_keys($metrics) as $key) {
            $metrics[$key] = in_array($key, ['linked_amount', 'successful_reversal_amount'])
                ? (float) ($row->{$key} ?? 0)
                : (int) ($row->{$key} ?? 0);
        }

        return $metrics;
    }

    protected function emptyMetrics()
    {
        return [
            'total_records' => 0,
            'linked_to_sales' => 0,
            'unlinked_ready' => 0,
            'picked' => 0,
            'pending' => 0,
            'failed_cancelled' => 0,
            'linked_amount' => 0.0,
            'reversal_pending_requested' => 0,
            'successful_reversal' => 0,
            'successful_reversal_amount' => 0.0,
        ];
    }

    protected function mergeMetrics(array $total, array $provider)
    {
        foreach ($total as $key => $value) {
            $total[$key] += $provider[$key];
        }

        return $total;
    }

    protected function enabledProviders($business_id)
    {
        return [
            'intasend' => $this->moduleUtil->isModuleEnabled('intasend', $business_id),
            'daraja' => $this->moduleUtil->isModuleEnabled('daraja', $business_id),
        ];
    }

    protected function authorizeDashboard(array $providers)
    {
        $user = auth()->user();
        $can_view = ($providers['intasend'] && $user->canAny(['intasend.transactions', 'intasend.manage']))
            || ($providers['daraja'] && $user->canAny(['daraja.transactions', 'daraja.manage']))
            || $user->can('superadmin');

        if (! ($providers['intasend'] || $providers['daraja']) || ! $can_view) {
            abort(403, 'Unauthorized action.');
        }
    }
}

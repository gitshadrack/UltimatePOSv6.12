<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\IntaSendPayment;
use App\IntaSendSetting;
use App\Utils\IntaSendUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class IntaSendController extends Controller
{
    protected $intasendUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    public function __construct(IntaSendUtil $intasendUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->intasendUtil = $intasendUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function webhook(Request $request)
    {
        if ($request->has('challenge') && ! $this->isPaymentCallback($request)) {
            return $this->webhookResponse(['challenge' => $request->input('challenge')], 200);
        }

        if ($request->isMethod('get')) {
            return $this->webhookResponse(['status' => 'ready'], 200);
        }

        try {
            if (! $this->hasIntaSendPaymentsSchema()) {
                return $this->webhookResponse(['status' => 'migration_required'], 503);
            }

            $normalized = $this->intasendUtil->normalizePayload($request->all());
            $setting = $this->intasendUtil->resolveSetting($normalized);

            if (! empty($setting) && ! $this->moduleUtil->isModuleEnabled('intasend', $setting->business_id)) {
                Log::info('IntaSend webhook ignored because the module is disabled.', [
                    'business_id' => $setting->business_id,
                    'transaction_code' => $normalized['transaction_code'] ?? null,
                    'till_number' => $normalized['till_number'] ?? null,
                ]);

                return $this->webhookResponse(['status' => 'disabled'], 200);
            }

            if (! $this->intasendUtil->verifyWebhookRequest($request, $setting)) {
                Log::warning('IntaSend webhook signature verification failed.', [
                    'transaction_code' => $normalized['transaction_code'] ?? null,
                    'till_number' => $normalized['till_number'] ?? null,
                ]);

                return $this->webhookResponse(['status' => 'unauthorized'], 401);
            }

            $payment = $this->intasendUtil->recordWebhookPayment($request->all());

            return $this->webhookResponse([
                'status' => 'success',
                'intasend_payment_id' => $payment->id,
                'reconciliation_status' => $payment->reconciliation_status,
                'is_attached' => (bool) $payment->is_attached,
            ], 200);
        } catch (\Exception $e) {
            Log::error('IntaSend webhook failed: '.$e->getMessage(), ['payload' => $request->all()]);

            return $this->webhookResponse(['status' => 'error'], 500);
        }
    }

    public function settings()
    {
        $this->authorizeManage();

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)
            ->Active()
            ->orderBy('name')
            ->get();
        $migration_required = ! $this->hasIntaSendSettingsSchema();
        $settings = collect();

        if (! $migration_required) {
            $settings = IntaSendSetting::where('business_id', $business_id)
                ->get()
                ->keyBy('business_location_id');
        }

        return view('intasend.settings', compact('locations', 'settings', 'migration_required'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeManage();

        $business_id = $request->session()->get('user.business_id');

        if (! $this->hasIntaSendSettingsSchema()) {
            return redirect()
                ->action([self::class, 'settings'])
                ->with('status', ['success' => false, 'msg' => __('lang_v1.run_intasend_migration')]);
        }

        $location_settings = $request->input('locations', []);

        foreach ($location_settings as $location_id => $input) {
            $location = BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);
            $data = [
                'business_id' => $business_id,
                'till_or_paybill_number' => trim((string) ($input['till_or_paybill_number'] ?? '')),
                'intasend_public_key' => trim((string) ($input['intasend_public_key'] ?? '')),
                'payment_link_url' => trim((string) ($input['payment_link_url'] ?? '')),
                'require_webhook_signature' => ! empty($input['require_webhook_signature']) ? 1 : 0,
                'is_active' => ! empty($input['is_active']) ? 1 : 0,
            ];

            if (! empty($input['intasend_secret_key'])) {
                $data['intasend_secret_key'] = trim((string) $input['intasend_secret_key']);
            }
            if (! empty($input['webhook_secret'])) {
                $data['webhook_secret'] = trim((string) $input['webhook_secret']);
            }

            IntaSendSetting::updateOrCreate(
                ['business_location_id' => $location->id],
                $data
            );
        }

        return redirect()
            ->action([self::class, 'settings'])
            ->with('status', ['success' => true, 'msg' => __('lang_v1.intasend_settings_saved')]);
    }

    public function pool(Request $request)
    {
        $this->authorizeManage();

        $business_id = $request->session()->get('user.business_id');
        $migration_required = ! $this->hasIntaSendPaymentsSchema();

        if ($request->ajax()) {
            if ($migration_required) {
                return DataTables::of(collect())->make(true);
            }

            $query = IntaSendPayment::leftJoin('business_locations as bl', 'intasend_payments.business_location_id', '=', 'bl.id')
                ->leftJoin('contacts as c', 'intasend_payments.contact_id', '=', 'c.id')
                ->leftJoin('transaction_payments as tp', 'intasend_payments.transaction_payment_id', '=', 'tp.id')
                ->where('intasend_payments.business_id', $business_id)
                ->select([
                    'intasend_payments.*',
                    'bl.name as location_name',
                    'c.name as contact_name',
                    'c.mobile as contact_mobile',
                    'tp.payment_ref_no',
                ]);

            if ($request->filled('status')) {
                $query->where('intasend_payments.reconciliation_status', $request->input('status'));
            }

            if ($request->filled('location_id')) {
                $query->where('intasend_payments.business_location_id', $request->input('location_id'));
            }

            return DataTables::of($query)
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                })
                ->editColumn('charges', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$this->transactionUtil->num_f($row->charges, true).'</span>';
                })
                ->editColumn('net_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$this->transactionUtil->num_f($row->net_amount, true).'</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $this->transactionUtil->format_date($row->created_at, true);
                })
                ->editColumn('match_reason', function ($row) {
                    return $row->match_reason ?: '--';
                })
                ->editColumn('match_note', function ($row) {
                    return $row->match_note ?: '--';
                })
                ->addColumn('customer', function ($row) {
                    if (! empty($row->contact_name)) {
                        return e($row->contact_name).' <small class="text-muted">'.e($row->contact_mobile).'</small>';
                    }

                    return '<span class="text-muted">'.__('lang_v1.unassigned').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    if (empty($row->is_attached) && strtoupper((string) $row->status) == 'COMPLETE') {
                        $buttons .= '<button type="button" class="btn btn-xs btn-primary attach-intasend-payment" data-id="'.$row->id.'">'.__('lang_v1.link_payment').'</button> ';
                    }
                    if (! empty($row->transaction_payment_id)) {
                        $buttons .= '<span class="label label-success">'.e($row->payment_ref_no).'</span>';
                    }

                    return $buttons ?: '--';
                })
                ->rawColumns(['amount', 'charges', 'net_amount', 'customer', 'action'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);

        return view('intasend.pool', compact('business_locations', 'customers', 'migration_required'));
    }

    public function collections(Request $request)
    {
        $this->authorizeManage();

        $business_id = $request->session()->get('user.business_id');
        $migration_required = ! $this->hasIntaSendPaymentsSchema();

        if ($request->ajax()) {
            if ($migration_required) {
                return DataTables::of(collect())->make(true);
            }

            $query = IntaSendPayment::leftJoin('business_locations as bl', 'intasend_payments.business_location_id', '=', 'bl.id')
                ->leftJoin('contacts as c', 'intasend_payments.contact_id', '=', 'c.id')
                ->where('intasend_payments.business_id', $business_id)
                ->where('intasend_payments.status', 'COMPLETE')
                ->select([
                    'intasend_payments.*',
                    'bl.name as location_name',
                    'c.name as contact_name',
                ]);

            if ($request->filled('location_id')) {
                $query->where('intasend_payments.business_location_id', $request->input('location_id'));
            }

            if ($request->filled('reconciliation_status')) {
                $query->where('intasend_payments.reconciliation_status', $request->input('reconciliation_status'));
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereDate('intasend_payments.created_at', '>=', $request->input('start_date'))
                    ->whereDate('intasend_payments.created_at', '<=', $request->input('end_date'));
            }

            return DataTables::of($query)
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $this->transactionUtil->format_date($row->created_at, true);
                })
                ->editColumn('contact_name', function ($row) {
                    return $row->contact_name ?: '--';
                })
                ->editColumn('match_reason', function ($row) {
                    return $row->match_reason ?: '--';
                })
                ->editColumn('auto_attached', function ($row) {
                    return ! empty($row->auto_attached) ? __('messages.yes') : __('messages.no');
                })
                ->with('total_collected', (clone $query)->sum('intasend_payments.amount'))
                ->with('total_charges', (clone $query)->sum('intasend_payments.charges'))
                ->with('total_net_amount', (clone $query)->sum('intasend_payments.net_amount'))
                ->rawColumns(['amount', 'charges', 'net_amount'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('intasend.collections', compact('business_locations', 'migration_required'));
    }

    public function attach(Request $request, $id)
    {
        $this->authorizeManage();

        $request->validate([
            'contact_id' => 'required|integer',
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $payment = IntaSendPayment::where('business_id', $business_id)->findOrFail($id);
            $this->intasendUtil->attachToCustomerDue($payment, $request->input('contact_id'), auth()->id(), false);

            $output = ['success' => true, 'msg' => __('lang_v1.intasend_payment_attached')];
        } catch (\Exception $e) {
            Log::error('IntaSend manual attachment failed: '.$e->getMessage());
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return $output;
    }

    public function posSearch(Request $request)
    {
        $this->authorizePosUse($request);

        if (! $this->hasIntaSendPaymentsSchema()) {
            return ['success' => false, 'msg' => __('lang_v1.run_intasend_migration'), 'payments' => []];
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');
        $transaction_code = trim((string) $request->input('transaction_code'));
        $phone_number = trim((string) $request->input('phone_number'));
        $amount = $request->filled('amount') ? (float) $request->input('amount') : null;

        if ($transaction_code === '' && $phone_number === '' && empty($amount)) {
            return ['success' => true, 'payments' => []];
        }

        $phone_variants = $this->phoneVariants($phone_number);

        $query = IntaSendPayment::where('business_id', $business_id)
            ->where('status', 'COMPLETE')
            ->where(function ($query) {
                $query->whereNull('transaction_payment_id')
                    ->where(function ($inner) {
                        $inner->where('is_attached', 0)
                            ->orWhereNull('is_attached');
                    });
            })
            ->where(function ($query) use ($transaction_code, $phone_variants, $amount) {
                if ($transaction_code !== '') {
                    $query->orWhere('transaction_code', $transaction_code);
                }

                if (! empty($phone_variants)) {
                    $query->orWhereIn('phone_number', $phone_variants);
                }

                if (! empty($amount)) {
                    $query->orWhereRaw('ABS(intasend_payments.amount - ?) <= 0.01', [$amount]);
                }
            })
            ->orderByDesc('created_at')
            ->limit(15);

        if (! empty($location_id)) {
            $query->where(function ($query) use ($location_id) {
                $query->where('business_location_id', $location_id)
                    ->orWhereNull('business_location_id');
            });
        }

        $query = $query->get();

        $payments = $query->map(function ($payment) use ($amount, $phone_variants, $transaction_code) {
            $reasons = [];
            if ($transaction_code !== '' && strtoupper((string) $payment->transaction_code) == strtoupper($transaction_code)) {
                $reasons[] = __('lang_v1.transaction_code');
            }
            if (! empty($phone_variants) && in_array($payment->phone_number, $phone_variants)) {
                $reasons[] = __('lang_v1.phone_number');
            }
            if (! empty($amount) && abs((float) $payment->amount - $amount) <= 0.01) {
                $reasons[] = __('sale.amount');
            }

            return [
                'id' => $payment->id,
                'transaction_code' => $payment->transaction_code,
                'phone_number' => $payment->phone_number,
                'amount' => (float) $payment->amount,
                'amount_formatted' => $this->transactionUtil->num_f($payment->amount, true),
                'net_amount' => (float) $payment->net_amount,
                'charges' => (float) $payment->charges,
                'status' => $payment->status,
                'created_at' => $this->transactionUtil->format_date($payment->created_at, true),
                'match_reason' => implode(', ', $reasons),
            ];
        });

        return ['success' => true, 'payments' => $payments];
    }

    public function sendStkPush(Request $request)
    {
        $this->authorizePosUse($request);

        $request->validate([
            'phone_number' => 'required',
            'amount' => 'required|numeric|min:1',
            'location_id' => 'nullable|integer',
            'contact_id' => 'nullable|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $setting = $this->activeSettingForPos($business_id, $request->input('location_id'));

        if (empty($setting) || empty($setting->intasend_secret_key)) {
            return ['success' => false, 'msg' => __('lang_v1.intasend_stk_missing_secret')];
        }

        $till_or_paybill_number = $this->normalizeTillOrPaybillNumber($setting->till_or_paybill_number ?? null);
        $api_ref = 'pos_stk_'.$business_id.'_'.auth()->id().'_'.time();
        if ($till_or_paybill_number !== '') {
            $api_ref .= '_till_'.$till_or_paybill_number;
        }
        if ($request->filled('contact_id')) {
            $api_ref .= '_customer_id_'.$request->input('contact_id');
        }

        $base_payload = [
            'amount' => (float) $request->input('amount'),
            'currency' => 'KES',
            'phone_number' => trim((string) $request->input('phone_number')),
            'api_ref' => $api_ref,
            'comment' => 'UltimatePOS POS STK Push',
        ];
        $payload = $base_payload;

        if ($till_or_paybill_number !== '') {
            $payload['till_identifier'] = $till_or_paybill_number;
            $payload['till_number'] = $till_or_paybill_number;
            $payload['metadata'] = [
                'till_identifier' => $till_or_paybill_number,
                'till_number' => $till_or_paybill_number,
                'business_location_id' => $setting->business_location_id,
                'source' => 'ultimate_pos',
            ];
        }

        try {
            $response = $this->postStkPush($setting, $payload);

            if (! $response->successful() && $payload !== $base_payload && in_array($response->status(), [400, 422], true)) {
                Log::warning('IntaSend STK push with till/paybill metadata failed; retrying with minimal payload.', [
                    'business_id' => $business_id,
                    'location_id' => $request->input('location_id'),
                    'endpoint' => $this->stkEndpoint($setting),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $response = $this->postStkPush($setting, $base_payload);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'msg' => __('lang_v1.intasend_stk_push_sent'),
                    'api_ref' => $api_ref,
                    'data' => $response->json(),
                ];
            }

            Log::warning('IntaSend STK push failed.', [
                'business_id' => $business_id,
                'location_id' => $request->input('location_id'),
                'endpoint' => $this->stkEndpoint($setting),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $response_data = $response->json();
            $error_detail = $response_data['errors'][0]['detail'] ?? null;

            return [
                'success' => false,
                'msg' => trim(__('lang_v1.intasend_stk_push_failed').' '.($error_detail ?: '')),
                'status' => $response->status(),
                'data' => $response_data,
            ];
        } catch (\Exception $e) {
            Log::error('IntaSend STK push exception: '.$e->getMessage());

            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    protected function authorizeManage()
    {
        if (! (auth()->user()->can('intasend.manage') || auth()->user()->can('superadmin'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (! $this->moduleUtil->isModuleEnabled('intasend', $business_id)) {
            abort(403, 'IntaSend module is disabled for this business.');
        }
    }

    protected function authorizePosUse(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isModuleEnabled('intasend', $business_id)) {
            abort(403, 'IntaSend module is disabled for this business.');
        }
    }

    protected function activeSettingForPos($business_id, $location_id = null)
    {
        $query = IntaSendSetting::where('business_id', $business_id)
            ->where('is_active', 1);

        if (! empty($location_id)) {
            $location_setting = (clone $query)
                ->where('business_location_id', $location_id)
                ->first();

            if (! empty($location_setting)) {
                return $location_setting;
            }
        }

        return $query->first();
    }

    protected function stkEndpoint(IntaSendSetting $setting)
    {
        return 'https://api.intasend.com/api/v1/payment/mpesa-stk-push/';
    }

    protected function postStkPush(IntaSendSetting $setting, array $payload)
    {
        return Http::withToken($setting->intasend_secret_key)
            ->asJson()
            ->timeout(20)
            ->post($this->stkEndpoint($setting), $payload);
    }

    protected function normalizeTillOrPaybillNumber($value)
    {
        return trim(preg_replace('/[^A-Za-z0-9-]/', '', (string) $value));
    }

    protected function phoneVariants($phone_number)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone_number);
        if ($digits === '') {
            return [];
        }

        $local = $digits;
        if (strpos($digits, '254') === 0 && strlen($digits) == 12) {
            $local = '0'.substr($digits, 3);
        }

        $international = $digits;
        if (strpos($digits, '0') === 0 && strlen($digits) == 10) {
            $international = '254'.substr($digits, 1);
        }

        return array_values(array_unique(array_filter([
            $digits,
            $local,
            $international,
            '+'.$international,
        ])));
    }

    protected function hasIntaSendSettingsSchema()
    {
        return Schema::hasTable('intasend_settings')
            && Schema::hasColumn('intasend_settings', 'webhook_secret')
            && Schema::hasColumn('intasend_settings', 'require_webhook_signature');
    }

    protected function hasIntaSendPaymentsSchema()
    {
        return Schema::hasTable('intasend_payments')
            && Schema::hasColumn('intasend_payments', 'match_reason')
            && Schema::hasColumn('intasend_payments', 'match_note')
            && Schema::hasColumn('intasend_payments', 'auto_attached')
            && Schema::hasColumn('intasend_payments', 'net_amount')
            && Schema::hasColumn('intasend_payments', 'charges')
            && Schema::hasColumn('intasend_payments', 'currency');
    }

    protected function isPaymentCallback(Request $request)
    {
        return $request->hasAny([
            'mpesa_reference',
            'provider_reference',
            'provider_ref',
            'invoice_id',
            'tracking_id',
            'state',
            'status',
            'topic',
        ]);
    }

    protected function webhookResponse(array $payload, $status = 200)
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}

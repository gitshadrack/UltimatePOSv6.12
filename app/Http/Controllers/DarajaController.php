<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\DarajaPayment;
use App\DarajaSetting;
use App\Utils\DarajaUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class DarajaController extends Controller
{
    protected $darajaUtil;
    protected $moduleUtil;
    protected $transactionUtil;

    public function __construct(DarajaUtil $darajaUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->darajaUtil = $darajaUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function settings(Request $request)
    {
        $this->authorizeSettings($request);
        $business_id = $request->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->Active()->orderBy('name')->get();
        $settings = $this->hasSchema()
            ? DarajaSetting::where('business_id', $business_id)->get()->keyBy('business_location_id')
            : collect();
        $migration_required = ! $this->hasSchema();

        return view('daraja.settings', compact('locations', 'settings', 'migration_required'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeSettings($request);
        $business_id = $request->session()->get('user.business_id');
        if (! $this->hasSchema()) {
            return back()->with('status', ['success' => false, 'msg' => __('lang_v1.run_daraja_migration')]);
        }

        foreach ($request->input('locations', []) as $location_id => $input) {
            $location = BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);
            $data = [
                'business_id' => $business_id,
                'environment' => in_array($input['environment'] ?? null, ['sandbox', 'production']) ? $input['environment'] : 'sandbox',
                'business_shortcode' => trim((string) ($input['business_shortcode'] ?? '')),
                'transaction_type' => in_array($input['transaction_type'] ?? null, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline']) ? $input['transaction_type'] : 'CustomerPayBillOnline',
                'account_reference' => trim((string) ($input['account_reference'] ?? 'UltimatePOS')),
                'callback_url' => trim((string) ($input['callback_url'] ?? '')),
                'confirmation_url' => trim((string) ($input['confirmation_url'] ?? '')),
                'validation_url' => trim((string) ($input['validation_url'] ?? '')),
                'c2b_response_type' => ($input['c2b_response_type'] ?? null) === 'Cancelled' ? 'Cancelled' : 'Completed',
                'is_active' => ! empty($input['is_active']),
            ];
            foreach (['consumer_key', 'consumer_secret', 'passkey'] as $secret) {
                if (! empty($input[$secret])) {
                    $data[$secret] = trim((string) $input[$secret]);
                }
            }

            DarajaSetting::updateOrCreate(['business_location_id' => $location->id], $data);
        }

        return back()->with('status', ['success' => true, 'msg' => __('lang_v1.daraja_settings_saved')]);
    }

    public function transactions(Request $request)
    {
        $this->authorizeTransactions($request);
        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            if (! $this->hasSchema()) {
                return DataTables::of(collect())->make(true);
            }

            $query = DarajaPayment::leftJoin('business_locations as bl', 'daraja_payments.business_location_id', '=', 'bl.id')
                ->leftJoin('contacts as c', 'daraja_payments.contact_id', '=', 'c.id')
                ->leftJoin('transaction_payments as tp', 'daraja_payments.transaction_payment_id', '=', 'tp.id')
                ->where('daraja_payments.business_id', $business_id)
                ->select(['daraja_payments.*', 'bl.name as location_name', 'c.name as contact_name', 'tp.payment_ref_no']);
            if ($request->filled('location_id')) {
                $query->where('daraja_payments.business_location_id', $request->input('location_id'));
            }
            if ($request->filled('status')) {
                $query->where('daraja_payments.status', $request->input('status'));
            }
            if ($request->filled('source')) {
                $query->where('daraja_payments.source', $request->input('source'));
            }

            return DataTables::of($query)
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$this->transactionUtil->num_f($row->amount, true).'</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $this->transactionUtil->format_date($row->created_at, true);
                })
                ->addColumn('action', function ($row) {
                    if (empty($row->is_attached) && $row->status === 'COMPLETE') {
                        return '<button type="button" class="btn btn-xs btn-primary attach-daraja-payment" data-id="'.$row->id.'">'.__('lang_v1.link_payment').'</button>';
                    }

                    return $row->payment_ref_no ? '<span class="label label-success">'.e($row->payment_ref_no).'</span>' : '--';
                })
                ->rawColumns(['amount', 'action'])->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $migration_required = ! $this->hasSchema();

        return view('daraja.transactions', compact('business_locations', 'customers', 'migration_required'));
    }

    public function attach(Request $request, $id)
    {
        $this->authorizeTransactions($request);
        $request->validate(['contact_id' => 'required|integer']);
        try {
            $business_id = $request->session()->get('user.business_id');
            $payment = DarajaPayment::where('business_id', $business_id)->findOrFail($id);
            $this->darajaUtil->attachToCustomerDue($payment, $request->input('contact_id'), auth()->id());

            return ['success' => true, 'msg' => __('lang_v1.daraja_payment_attached')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function sendStkPush(Request $request)
    {
        $this->authorizePosUse($request);
        $request->validate(['phone_number' => 'required', 'amount' => 'required|numeric|min:1', 'location_id' => 'required|integer', 'contact_id' => 'nullable|integer']);
        $business_id = $request->session()->get('user.business_id');
        $setting = $this->activeSetting($business_id, $request->input('location_id'));
        if (empty($setting) || empty($setting->consumer_key) || empty($setting->consumer_secret) || empty($setting->passkey)) {
            return ['success' => false, 'msg' => __('lang_v1.daraja_credentials_missing')];
        }

        try {
            $result = $this->darajaUtil->sendStkPush($setting, $request->input('phone_number'), $request->input('amount'), $request->input('contact_id'));
            return [
                'success' => $result['accepted'],
                'msg' => $result['accepted'] ? __('lang_v1.daraja_stk_push_sent') : __('lang_v1.daraja_stk_push_failed'),
                'data' => $result['data'],
            ];
        } catch (\Exception $e) {
            Log::error('Daraja STK push failed: '.$e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function posSearch(Request $request)
    {
        $this->authorizePosUse($request);
        $business_id = $request->session()->get('user.business_id');
        $query = DarajaPayment::where('business_id', $business_id)->where('status', 'COMPLETE')->where('is_attached', 0);
        if ($request->filled('location_id')) {
            $query->where('business_location_id', $request->input('location_id'));
        }
        $transaction_code = strtoupper(trim((string) $request->input('transaction_code')));
        $phone = $this->darajaUtil->normalizePhone($request->input('phone_number'));
        $amount = $request->filled('amount') ? (float) $request->input('amount') : null;
        $query->where(function ($query) use ($transaction_code, $phone, $amount) {
            if ($transaction_code !== '') {
                $query->orWhere('transaction_code', $transaction_code);
            }
            if ($phone !== '') {
                $query->orWhere('phone_number', $phone);
            }
            if (! empty($amount)) {
                $query->orWhereRaw('ABS(amount - ?) <= 0.01', [$amount]);
            }
        });

        $payments = $query->orderByDesc('created_at')->limit(15)->get()->map(function ($payment) {
            return [
                'id' => $payment->id,
                'transaction_code' => $payment->transaction_code,
                'phone_number' => $payment->phone_number,
                'amount' => (float) $payment->amount,
                'amount_formatted' => $this->transactionUtil->num_f($payment->amount, true),
                'status' => $payment->status,
                'created_at' => $this->transactionUtil->format_date($payment->created_at, true),
            ];
        });

        return ['success' => true, 'payments' => $payments];
    }

    public function callback(Request $request, $setting)
    {
        try {
            $setting = DarajaSetting::findOrFail($setting);
            $this->verifyCallbackToken($request, $setting);
            $payment = $this->darajaUtil->recordStkCallback($setting, $request->all());
            Log::info('Daraja STK callback processed.', ['daraja_payment_id' => $payment->id]);
        } catch (\Exception $e) {
            Log::warning('Daraja STK callback ignored: '.$e->getMessage(), ['payload' => $request->all()]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function c2bValidation(Request $request, $setting)
    {
        $setting = DarajaSetting::findOrFail($setting);
        $this->verifyCallbackToken($request, $setting);
        $shortcode_matches = empty($request->input('BusinessShortCode')) || (string) $request->input('BusinessShortCode') === (string) $setting->business_shortcode;

        return response()->json([
            'ResultCode' => $shortcode_matches ? 0 : 1,
            'ResultDesc' => $shortcode_matches ? 'Accepted' : 'Invalid shortcode',
        ]);
    }

    public function c2bConfirmation(Request $request, $setting)
    {
        try {
            $setting = DarajaSetting::findOrFail($setting);
            $this->verifyCallbackToken($request, $setting);
            if (! empty($request->input('BusinessShortCode'))
                && (string) $request->input('BusinessShortCode') !== (string) $setting->business_shortcode) {
                throw new \Exception('Invalid Daraja business shortcode.');
            }
            $this->darajaUtil->recordC2bPayment($setting, $request->all());
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        } catch (\Exception $e) {
            Log::error('Daraja C2B confirmation failed: '.$e->getMessage(), ['payload' => $request->all()]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Rejected']);
        }
    }

    public function registerUrls(Request $request, $setting_id)
    {
        $this->authorizeSettings($request);
        $business_id = $request->session()->get('user.business_id');
        $setting = DarajaSetting::where('business_id', $business_id)->findOrFail($setting_id);
        try {
            $result = $this->darajaUtil->registerC2bUrls($setting);
            return ['success' => $result['successful'], 'msg' => $result['successful'] ? __('lang_v1.daraja_urls_registered') : __('lang_v1.daraja_urls_registration_failed'), 'data' => $result['data']];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    protected function activeSetting($business_id, $location_id)
    {
        return DarajaSetting::where('business_id', $business_id)->where('business_location_id', $location_id)->where('is_active', 1)->first();
    }

    protected function authorizeSettings(Request $request)
    {
        if (! (auth()->user()->can('daraja.settings') || auth()->user()->can('daraja.manage') || auth()->user()->can('superadmin'))) {
            abort(403, 'Unauthorized action.');
        }
        $this->authorizeModule($request);
    }

    protected function authorizeTransactions(Request $request)
    {
        if (! (auth()->user()->can('daraja.transactions') || auth()->user()->can('daraja.manage') || auth()->user()->can('superadmin'))) {
            abort(403, 'Unauthorized action.');
        }
        $this->authorizeModule($request);
    }

    protected function authorizePosUse(Request $request)
    {
        $this->authorizeModule($request);
    }

    protected function authorizeModule(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isModuleEnabled('daraja', $business_id)) {
            abort(403, 'Daraja module is disabled for this business.');
        }
    }

    protected function hasSchema()
    {
        return Schema::hasTable('daraja_settings') && Schema::hasTable('daraja_payments');
    }

    protected function verifyCallbackToken(Request $request, DarajaSetting $setting)
    {
        if (empty($setting->callback_token)
            || ! hash_equals((string) $setting->callback_token, (string) $request->query('token'))) {
            abort(403, 'Invalid Daraja callback token.');
        }
    }
}

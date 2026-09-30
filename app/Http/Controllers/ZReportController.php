<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Utils\TransactionUtil;
use App\Utils\ZReportUtil;
use Illuminate\Http\Request;

class ZReportController extends Controller
{
    public function index(Request $request, ZReportUtil $reportUtil, TransactionUtil $transactionUtil)
    {
        abort_unless($request->user()->can('register_report.view'), 403);
        $request->validate(['date' => 'nullable|date_format:Y-m-d', 'location_id' => 'nullable|integer']);
        $businessId = $request->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $businessId);
        $permitted = $request->user()->permitted_locations();
        if ($permitted !== 'all') {
            $locations->whereIn('id', $permitted);
        }
        $locations = $locations->pluck('name', 'id');
        $locationId = $request->input('location_id');
        if ($locationId !== null && $locationId !== '') {
            abort_unless($locations->has($locationId), 403);
        }
        $date = $request->input('date') ?: now()->format('Y-m-d');
        $userId = $request->user()->can('view_all_cash_register') ? null : $request->user()->id;
        $summary = $reportUtil->getSummary($businessId, $locationId ? [(int) $locationId] : $locations->keys()->all(), $date, $userId);
        $paymentTypes = $transactionUtil->payment_types(null, true, $businessId);

        return view('report.z_report', compact('locations', 'locationId', 'date', 'summary', 'paymentTypes', 'userId'));
    }
}

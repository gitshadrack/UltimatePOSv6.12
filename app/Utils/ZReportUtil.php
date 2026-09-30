<?php

namespace App\Utils;

use Illuminate\Support\Facades\DB;

class ZReportUtil
{
    public function getSummary($businessId, array $locationIds, $date, $userId = null)
    {
        $start = \Carbon\Carbon::parse($date)->startOfDay();
        $end = $start->copy()->addDay();
        $base = DB::table('transactions as t')
            ->where('t.business_id', $businessId)
            ->whereIn('t.location_id', $locationIds);
        if ($userId !== null) {
            $base->where('t.created_by', $userId);
        }
        $transactions = (clone $base)->where('t.transaction_date', '>=', $start)
            ->where('t.transaction_date', '<', $end)
            ->whereIn('t.type', ['sell', 'sell_return', 'expense', 'expense_refund'])
            ->where('t.status', 'final')
            ->selectRaw('t.type, COUNT(*) as count, SUM(t.final_total) as total, SUM(t.tax_amount) as tax')
            ->groupBy('t.type')->get()->keyBy('type');
        $sales = $transactions->get('sell');
        $returns = $transactions->get('sell_return');
        $payments = (clone $base)->join('transaction_payments as p', 'p.transaction_id', '=', 't.id')
            ->where('t.status', 'final')->whereIn('t.type', ['sell', 'sell_return'])
            ->where('p.paid_on', '>=', $start)->where('p.paid_on', '<', $end)
            ->selectRaw("p.method, SUM(CASE WHEN t.type = 'sell_return' OR p.is_return = 1 THEN -p.amount ELSE p.amount END) as total")
            ->groupBy('p.method')->orderBy('p.method')->get();

        return [
            'sales_count' => (int) ($sales->count ?? 0),
            'sales_total' => (float) ($sales->total ?? 0),
            'returns_total' => (float) ($returns->total ?? 0),
            'net_sales' => (float) ($sales->total ?? 0) - (float) ($returns->total ?? 0),
            'net_tax' => (float) ($sales->tax ?? 0) - (float) ($returns->tax ?? 0),
            'expenses_total' => (float) ($transactions->get('expense')->total ?? 0) - (float) ($transactions->get('expense_refund')->total ?? 0),
            'payments' => $payments,
        ];
    }
}

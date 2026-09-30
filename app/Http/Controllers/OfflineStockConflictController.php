<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\OfflineStockConflictResolution;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfflineStockConflictController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index(Request $request)
    {
        $this->authorizeManager();
        $businessId = $request->session()->get('user.business_id');
        $conflictsQuery = Transaction::where('business_id', $businessId)
            ->where('offline_sync_status', 'manager_review')
            ->with(['location', 'contact', 'sales_person', 'sell_lines.product', 'sell_lines.variations'])
            ->orderBy('offline_created_at');
        $locationQuery = BusinessLocation::where('business_id', $businessId)->where('is_active', 1);
        $permitted = auth()->user()->permitted_locations();
        if ($permitted !== 'all') {
            $conflictsQuery->whereIn('location_id', $permitted);
            $locationQuery->whereIn('id', $permitted);
        }
        $conflicts = $conflictsQuery->get();
        $locations = $locationQuery->pluck('name', 'id');

        return view('offline_stock_conflicts.index', compact('conflicts', 'locations'));
    }

    public function approve(Request $request, $id)
    {
        $this->authorizeStockManager();
        return $this->resolve($request, $id, 'force_adjustment');
    }

    public function reassign(Request $request, $id)
    {
        $this->authorizeStockManager();
        $request->validate(['source_location_id' => 'required|integer']);
        return $this->resolve($request, $id, 'reassign_location');
    }

    public function void(Request $request, $id)
    {
        if (! auth()->user()->hasAnyPermission(['sell.delete', 'direct_sell.delete', 'access_sell_return'])) abort(403);
        return $this->resolve($request, $id, 'void_sale');
    }

    private function resolve(Request $request, $id, $action)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        DB::beginTransaction();
        try {
            $sale = Transaction::where('business_id', $businessId)
                ->where('offline_sync_status', 'manager_review')
                ->with(['sell_lines.sub_unit'])
                ->lockForUpdate()->findOrFail($id);
            $this->ensureLocationPermitted($sale->location_id);
            $details = [];
            $adjustmentId = null;
            $creditNoteId = null;
            $deficits = $this->deficitsForSale($sale);

            if ($action === 'force_adjustment') {
                if (! empty($deficits)) {
                    $refCount = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
                    $adjustment = Transaction::create([
                        'business_id' => $businessId, 'location_id' => $sale->location_id,
                        'type' => 'stock_adjustment', 'status' => 'final', 'adjustment_type' => 'normal',
                        'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $refCount),
                        'transaction_date' => now(), 'total_amount_recovered' => 0, 'final_total' => 0,
                        'additional_notes' => 'Automatic inbound correction for offline sale '.$sale->invoice_no,
                        'created_by' => auth()->id(),
                    ]);
                    foreach ($deficits as $line) {
                        DB::table('variation_location_details')->where($line['keys'])->increment('qty_available', $line['deficit']);
                        $adjustment->stock_adjustment_lines()->create([
                            'product_id' => $line['product_id'], 'variation_id' => $line['variation_id'],
                            'quantity' => -$line['deficit'], 'unit_price' => 0,
                        ]);
                    }
                    $adjustmentId = $adjustment->id;
                    $details = $deficits;
                }
                $sale->offline_sync_status = 'resolved_adjustment';
            } elseif ($action === 'reassign_location') {
                $sourceId = (int) $request->input('source_location_id');
                BusinessLocation::where('business_id', $businessId)->where('id', $sourceId)->where('is_active', 1)->firstOrFail();
                $this->ensureLocationPermitted($sourceId);
                if ($sourceId === (int) $sale->location_id) throw new \RuntimeException('Choose a different source location.');
                foreach ($deficits as $line) {
                    $source = DB::table('variation_location_details')->where('location_id', $sourceId)
                        ->where('product_id', $line['product_id'])->where('variation_id', $line['variation_id'])->lockForUpdate()->first();
                    if (! $source || (float) $source->qty_available < $line['deficit']) {
                        throw new \RuntimeException('The source location does not have enough stock for variation '.$line['variation_id'].'.');
                    }
                    DB::table('variation_location_details')->where('id', $source->id)->decrement('qty_available', $line['deficit']);
                    DB::table('variation_location_details')->where($line['keys'])->increment('qty_available', $line['deficit']);
                }
                $details = $deficits;
                $sale->offline_sync_status = 'resolved_reassigned';
            } else {
                $products = [];
                foreach ($sale->sell_lines as $line) {
                    $multiplier = $line->sub_unit ? (float) $line->sub_unit->base_unit_multiplier : 1;
                    $remaining = (float) $line->quantity - ((float) $line->quantity_returned / $multiplier);
                    if ($remaining > 0) $products[] = ['sell_line_id' => $line->id, 'quantity' => $remaining, 'unit_price_inc_tax' => $line->unit_price_inc_tax];
                }
                $credit = $this->transactionUtil->addSellReturn([
                    'transaction_id' => $sale->id, 'products' => $products,
                    'discount_type' => $sale->discount_type ?: 'fixed', 'discount_amount' => $sale->discount_amount ?: 0,
                    'tax_id' => $sale->tax_id,
                ], $businessId, auth()->id(), false);
                $creditNoteId = $credit->id;
                $details = ['credit_note_invoice' => $credit->invoice_no];
                $sale->offline_sync_status = 'resolved_voided';
            }

            $sale->offline_sync_note = json_encode(['resolution' => $action, 'details' => $details]);
            $sale->save();
            OfflineStockConflictResolution::create([
                'business_id' => $businessId, 'transaction_id' => $sale->id, 'action' => $action,
                'source_location_id' => $request->input('source_location_id'),
                'adjustment_transaction_id' => $adjustmentId, 'credit_note_transaction_id' => $creditNoteId,
                'resolved_by' => auth()->id(), 'details' => $details,
            ]);
            $this->transactionUtil->activityLog($sale, 'edited', null, ['offline_conflict_resolution' => $action], false);
            DB::commit();
            return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Offline stock conflict resolved.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->back()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    private function deficitsForSale(Transaction $sale)
    {
        $deficits = [];
        foreach ($sale->sell_lines as $line) {
            $multiplier = $line->sub_unit ? (float) $line->sub_unit->base_unit_multiplier : 1;
            $soldQuantity = max(0, ((float) $line->quantity * $multiplier) - (float) $line->quantity_returned);
            if ($soldQuantity <= 0) continue;
            if (isset($deficits[$line->variation_id])) {
                $deficits[$line->variation_id]['sold_quantity'] += $soldQuantity;
                $deficits[$line->variation_id]['deficit'] = min(
                    $deficits[$line->variation_id]['negative_quantity'],
                    $deficits[$line->variation_id]['sold_quantity']
                );
                continue;
            }
            $stock = DB::table('variation_location_details')->where('location_id', $sale->location_id)
                ->where('product_id', $line->product_id)->where('variation_id', $line->variation_id)->lockForUpdate()->first();
            if ($stock && (float) $stock->qty_available < 0) {
                $deficits[$line->variation_id] = [
                    'product_id' => $line->product_id, 'variation_id' => $line->variation_id,
                    'deficit' => min(abs((float) $stock->qty_available), $soldQuantity),
                    'negative_quantity' => abs((float) $stock->qty_available),
                    'sold_quantity' => $soldQuantity,
                    'keys' => ['id' => $stock->id],
                ];
            }
        }
        return array_values($deficits);
    }

    private function authorizeManager()
    {
        if (! auth()->user()->hasAnyPermission(['sell.view', 'stock_adjustment.view', 'stock_adjustment.create'])) abort(403);
    }

    private function authorizeStockManager()
    {
        if (! auth()->user()->can('stock_adjustment.create')) abort(403);
    }

    private function ensureLocationPermitted($locationId)
    {
        $permitted = auth()->user()->permitted_locations();
        if ($permitted !== 'all' && ! in_array((int) $locationId, array_map('intval', $permitted), true)) abort(403);
    }
}

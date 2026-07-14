<?php
/**
 * Damage Management Module
 * 
 * Comprehensive Damage Management Module - Track damaged products, manage dispatches 
 * to suppliers, and handle compensation claims with full documentation and reporting.
 * 
 * Module: DamageManagement
 * Author: Hackermiind
 * Version: 1.0.0
 * 
 * This is a complete free module for non commercial use.
 * 
 * @package Modules\DamageManagement
 */

namespace Modules\DamageManagement\Http\Controllers;

use App\BusinessLocation;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Modules\DamageManagement\Entities\DamageRecord;
use Modules\DamageManagement\Entities\DispatchDamage;
use Modules\DamageManagement\Entities\DispatchDamageLine;
use Carbon\Carbon;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DamageDispatchController extends Controller
{
    /**
     * @var ProductUtil
     */
    protected $productUtil;

    /**
     * @var TransactionUtil
     */
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('damage_dispatch.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $businessId = request()->session()->get('user.business_id');

            $dispatches = DispatchDamage::leftJoin('business_locations as BL', 'dispatch_damages.location_id', '=', 'BL.id')
                ->leftJoin('users as U', 'dispatch_damages.created_by', '=', 'U.id')
                ->where('dispatch_damages.business_id', $businessId)
                ->select(
                    'dispatch_damages.id',
                    'dispatch_damages.reference_no',
                    'dispatch_damages.dispatched_at',
                    'dispatch_damages.total_purchase_value',
                    'dispatch_damages.total_sell_value',
                    'dispatch_damages.total_compensation_value',
                    'dispatch_damages.status',
                    'dispatch_damages.notes',
                    'BL.name as location_name',
                    DB::raw("CONCAT(COALESCE(U.surname, ''), ' ', COALESCE(U.first_name, ''), ' ', COALESCE(U.last_name, '')) as created_by_name")
                );

            $permittedLocations = auth()->user()->permitted_locations();
            if ($permittedLocations != 'all') {
                $dispatches->whereIn('dispatch_damages.location_id', $permittedLocations);
            }

            if ($locationId = request()->get('location_id')) {
                $dispatches->where('dispatch_damages.location_id', $locationId);
            }

            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            if (!empty($startDate) && !empty($endDate)) {
                $dispatches->whereBetween(DB::raw('date(dispatch_damages.dispatched_at)'), [$startDate, $endDate]);
            }

            return Datatables::of($dispatches)
                ->editColumn('dispatched_at', '{{@format_datetime($dispatched_at)}}')
                ->editColumn('total_purchase_value', function ($row) {
                    return $this->transactionUtil->num_f($row->total_purchase_value, true);
                })
                ->editColumn('total_sell_value', function ($row) {
                    return $this->transactionUtil->num_f($row->total_sell_value, true);
                })
                ->editColumn('total_compensation_value', function ($row) {
                    return $this->transactionUtil->num_f($row->total_compensation_value, true);
                })
                ->addColumn('action', function ($row) {
                    $actions = '';
                    if (auth()->user()->can('damage_dispatch.view')) {
                        $actions .= '<button type="button" class="btn btn-xs btn-primary btn-modal" data-href="'.
                            action([self::class, 'show'], [$row->id]).
                            '" data-container=".view_modal"><i class="fa fa-eye"></i> '.__('messages.view').'</button> ';
                        $actions .= '<a href="'.
                            action([self::class, 'print'], [$row->id]).
                            '" class="btn btn-xs btn-default" target="_blank"><i class="fa fa-print"></i> '.__('messages.print').'</a> ';
                    }

                    if (auth()->user()->can('damage_dispatch.delete')) {
                        $actions .= '<button type="button" class="btn btn-xs btn-danger delete-damage-dispatch" data-href="'.
                            action([self::class, 'destroy'], [$row->id]).
                            '"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>';
                    }

                    return $actions;
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($businessId);

        return view('damagemanagement::dispatch.index', compact('locations'));
    }

    public function availableRecords()
    {
        if (!auth()->user()->can('damage_dispatch.create') && !auth()->user()->can('damage_dispatch.update')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locationId = request()->get('location_id');

        $records = DamageRecord::leftJoin('products as P', 'damage_records.product_id', '=', 'P.id')
            ->leftJoin('variations as V', 'damage_records.variation_id', '=', 'V.id')
            ->leftJoin('business_locations as BL', 'damage_records.location_id', '=', 'BL.id')
            ->where('damage_records.business_id', $businessId)
            ->where('damage_records.dispatch_status', '!=', 'dispatched')
            ->select(
                'damage_records.id',
                'damage_records.reference_no',
                'damage_records.quantity',
                'damage_records.dispatched_quantity',
                'damage_records.purchase_value',
                'damage_records.sell_value',
                'damage_records.expected_compensation',
                'damage_records.unit_purchase_price',
                'damage_records.unit_sell_price',
                'damage_records.reported_at',
                'damage_records.location_id',
                'P.name as product_name',
                'V.sub_sku',
                'BL.name as location_name'
            );

        $permittedLocations = auth()->user()->permitted_locations();
        if ($permittedLocations != 'all') {
            $records->whereIn('damage_records.location_id', $permittedLocations);
        }

        if (!empty($locationId)) {
            $records->where('damage_records.location_id', $locationId);
        }

        return Datatables::of($records)
            ->addColumn('remaining_quantity_raw', function ($row) {
                return (float) $row->quantity - (float) $row->dispatched_quantity;
            })
            ->addColumn('expected_compensation_raw', function ($row) {
                return (float) $row->expected_compensation;
            })
            ->addColumn('remaining_quantity', function ($row) {
                $remaining = (float) $row->quantity - (float) $row->dispatched_quantity;

                return $this->transactionUtil->num_f(max($remaining, 0), false, null, true);
            })
            ->editColumn('quantity', function ($row) {
                return $this->transactionUtil->num_f($row->quantity, false, null, true);
            })
            ->editColumn('dispatched_quantity', function ($row) {
                return $this->transactionUtil->num_f($row->dispatched_quantity, false, null, true);
            })
            ->editColumn('purchase_value', function ($row) {
                return $this->transactionUtil->num_f($row->purchase_value, true);
            })
            ->editColumn('sell_value', function ($row) {
                return $this->transactionUtil->num_f($row->sell_value, true);
            })
            ->editColumn('expected_compensation', function ($row) {
                return $this->transactionUtil->num_f($row->expected_compensation, true);
            })
            ->editColumn('reported_at', '{{@format_datetime($reported_at)}}')
            ->addColumn('product_display', function ($row) {
                return $row->product_name.' ('.$row->sub_sku.')';
            })
            ->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-xs btn-success add-damage-record" data-id="'.$row->id.'"><i class="fa fa-plus"></i></button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        if (!auth()->user()->can('damage_dispatch.create')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($businessId);

        return view('damagemanagement::dispatch.create', compact('locations'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('damage_dispatch.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'records' => 'required|array|min:1',
            'records.*.id' => 'required|integer|exists:damage_records,id',
            'records.*.dispatch_quantity' => 'required',
            'records.*.compensation_amount' => 'nullable',
            'dispatched_at' => 'required|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
            'reference_no' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $businessId = $request->session()->get('user.business_id');
            $userId = $request->session()->get('user.id');
            $dispatchedAt = $this->productUtil->uf_date($request->input('dispatched_at'), true);

            $reference = $request->input('reference_no');
            if (empty($reference)) {
                $reference = DispatchDamage::generateReference(Carbon::parse($dispatchedAt), $businessId);
            }

            $dispatch = DispatchDamage::create([
                'business_id' => $businessId,
                'location_id' => $request->input('location_id'),
                'reference_no' => $reference,
                'dispatched_at' => $dispatchedAt,
                'created_by' => $userId,
                'status' => 'final',
                'notes' => $request->input('notes'),
            ]);

            $totalPurchase = 0;
            $totalSell = 0;
            $totalCompensation = 0;

            $lineInputs = $request->input('records');

            $recordIds = collect($lineInputs)->pluck('id')->all();
            $records = DamageRecord::where('business_id', $businessId)
                ->whereIn('id', $recordIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Create main transaction for the dispatch
            $transaction = \App\Transaction::create([
                'business_id' => $businessId,
                'location_id' => $request->input('location_id'),
                'type' => 'damage_dispatch',
                'status' => 'final',
                'transaction_date' => Carbon::parse($dispatchedAt)->format('Y-m-d H:i:s'),
                'ref_no' => $reference,
                'total_before_tax' => 0,
                'final_total' => 0,
                'created_by' => $userId,
                'additional_notes' => $request->input('notes'),
            ]);
            
            $dispatch->transaction_id = $transaction->id;
            $dispatch->save();

            foreach ($lineInputs as $line) {
                $recordId = (int) $line['id'];
                if (!$records->has($recordId)) {
                    throw new \Exception(__('damage.invalid_record'));
                }

                $record = $records->get($recordId);
                $dispatchQty = $this->productUtil->num_uf($line['dispatch_quantity']);
                $compensationAmount = $this->productUtil->num_uf($line['compensation_amount'] ?? 0);

                $remainingQty = $record->quantity - $record->dispatched_quantity;
                if ($dispatchQty <= 0 || $dispatchQty > $remainingQty + 0.0001) {
                    throw new \Exception(__('damage.invalid_dispatch_quantity'));
                }

                $linePurchase = $dispatchQty * $record->unit_purchase_price;
                $lineSell = $dispatchQty * $record->unit_sell_price;

                // Decrease product quantity
                $this->productUtil->decreaseProductQuantity(
                    $record->product_id,
                    $record->variation_id,
                    $request->input('location_id'),
                    $dispatchQty
                );

                // Create stock adjustment line for stock history tracking
                $adjustmentLine = \App\StockAdjustmentLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $record->product_id,
                    'variation_id' => $record->variation_id,
                    'quantity' => $dispatchQty,
                    'unit_price' => $record->unit_purchase_price,
                ]);

                DispatchDamageLine::create([
                    'dispatch_damage_id' => $dispatch->id,
                    'transaction_id' => $transaction->id,
                    'damage_record_id' => $record->id,
                    'dispatched_quantity' => $dispatchQty,
                    'purchase_value' => $linePurchase,
                    'sell_value' => $lineSell,
                    'compensation_amount' => $compensationAmount,
                ]);

                $record->dispatched_quantity += $dispatchQty;
                if ($record->dispatched_quantity >= $record->quantity) {
                    $record->dispatch_status = 'dispatched';
                    $record->dispatched_quantity = $record->quantity;
                } elseif ($record->dispatched_quantity > 0) {
                    $record->dispatch_status = 'partial';
                }
                $record->given_compensation = ($record->given_compensation ?? 0) + $compensationAmount;
                $record->save();

                $totalPurchase += $linePurchase;
                $totalSell += $lineSell;
                $totalCompensation += $compensationAmount;
            }

            $dispatch->total_purchase_value = $totalPurchase;
            $dispatch->total_sell_value = $totalSell;
            $dispatch->total_compensation_value = $totalCompensation;
            $dispatch->save();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('damage.dispatch_created'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage dispatch store error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return $output;
        }

        if ($output['success']) {
            return redirect()->action([self::class, 'index'])->with('status', $output);
        }

        return redirect()->back()->with('status', $output);
    }

    public function show($id)
    {
        if (!auth()->user()->can('damage_dispatch.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $dispatch = DispatchDamage::with([
                'lines.damageRecord.product',
                'lines.damageRecord.variation',
                'lines.damageRecord.location',
                'lines.damageRecord.customer',
                'lines.damageRecord.supplier',
            ])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view('damagemanagement::dispatch.show', compact('dispatch'));
    }

    public function print($id)
    {
        if (!auth()->user()->can('damage_dispatch.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $dispatch = DispatchDamage::with([
                'lines.damageRecord.product',
                'lines.damageRecord.variation',
                'lines.damageRecord.location',
                'lines.damageRecord.customer',
                'lines.damageRecord.supplier',
            ])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view('damagemanagement::dispatch.print', compact('dispatch'));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('damage_dispatch.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $businessId = request()->session()->get('user.business_id');
            $dispatch = DispatchDamage::where('business_id', $businessId)->findOrFail($id);

            $lines = $dispatch->lines()->lockForUpdate()->get();

            foreach ($lines as $line) {
                $record = DamageRecord::where('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($line->damage_record_id);

                // Increase product quantity back using the correct method
                $this->productUtil->updateProductQuantity(
                    $dispatch->location_id,
                    $record->product_id,
                    $record->variation_id,
                    $line->dispatched_quantity,  // This adds stock back
                    0,
                    null,
                    false
                );

                $record->dispatched_quantity -= $line->dispatched_quantity;
                if ($record->dispatched_quantity < 0) {
                    $record->dispatched_quantity = 0;
                }

                if ($record->dispatched_quantity <= 0) {
                    $record->dispatch_status = 'not_dispatched';
                } elseif ($record->dispatched_quantity < $record->quantity) {
                    $record->dispatch_status = 'partial';
                } else {
                    $record->dispatch_status = 'dispatched';
                }

                $record->given_compensation = ($record->given_compensation ?? 0) - $line->compensation_amount;
                if ($record->given_compensation < 0) {
                    $record->given_compensation = 0;
                }

                $record->save();
            }

            // Delete the associated transactions and stock adjustment lines
            // Method 1: Delete by transaction_id if exists
            if ($dispatch->transaction_id) {
                // Delete stock adjustment lines first
                \App\StockAdjustmentLine::where('transaction_id', $dispatch->transaction_id)->delete();
                // Delete the transaction itself
                \App\Transaction::where('id', $dispatch->transaction_id)->delete();
            }
            
            // Method 2: Also delete by reference number (in case transaction_id is missing for old records)
            if ($dispatch->reference_no) {
                // Find transactions with this reference
                $transactions = \App\Transaction::where('business_id', $businessId)
                    ->where('ref_no', $dispatch->reference_no)
                    ->where('type', 'damage_dispatch')
                    ->get();
                
                foreach ($transactions as $trans) {
                    // Delete stock adjustment lines
                    \App\StockAdjustmentLine::where('transaction_id', $trans->id)->delete();
                    // Delete the transaction
                    \App\Transaction::where('id', $trans->id)->delete();
                }
            }

            $dispatch->lines()->delete();
            $dispatch->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('damage.dispatch_deleted'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage dispatch delete error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}

<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Events\StockAdjustmentCreatedOrModified;

class StockAdjustmentController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (! auth()->user()->can('stock_adjustment.view') && ! auth()->user()->can('stock_adjustment.create') && ! auth()->user()->can('view_own_stock_adjustment') && ! auth()->user()->can('stock_adjustment.reset')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $stock_adjustments = Transaction::join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'stock_adjustment')
                    ->select(
                        'transactions.id',
                        'transaction_date',
                        'ref_no',
                        'BL.name as location_name',
                        'adjustment_type',
                        'final_total',
                        'total_amount_recovered',
                        'additional_notes',
                        'transactions.id as DT_RowId',
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                    );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $stock_adjustments->whereIn('transactions.location_id', $permitted_locations);
            }

            $hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (! empty($start_date) && ! empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }
            $location_id = request()->get('location_id');
            if (! empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            if (! auth()->user()->can('stock_adjustment.view')) {
                if (auth()->user()->can('view_own_stock_adjustment')) {
                    $stock_adjustments->where('transactions.created_by', request()->session()->get('user.id'));
                } else {
                    $stock_adjustments->whereRaw('0 = 1');
                }
            }

            if(! auth()->user()->can('stock_adjustment.delete')){
                $hide = 'hide';
            }

            return Datatables::of($stock_adjustments)
                ->addColumn('action', '<button type="button" data-href="{{action([\App\Http\Controllers\StockAdjustmentController::class, \'show\'], [$id]) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-primary btn-modal" data-container=".view_modal"><i class="fa fa-eye" aria-hidden="true"></i> @lang("messages.view")</button>
                 &nbsp;
                    <button type="button" data-href="{{  action([\App\Http\Controllers\StockAdjustmentController::class, \'destroy\'], [$id]) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_stock_adjustment '.$hide.'"><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</button>')
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    function ($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return $this->transactionUtil->num_f($row->final_total, true);                     
                         } else {
                            return '<span>-</span>';
                        }
                        
                    }
                )

                ->editColumn(
                    'total_amount_recovered',
                    function ($row) {
                        if (auth()->user()->can('view_purchase_price')) {
                            return $this->transactionUtil->num_f($row->total_amount_recovered, true);                    
                         } else {
                            return '<span>-</span>';
                        }
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('adjustment_type', function ($row) {
                    return __('stock_adjustment.'.$row->adjustment_type);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$row->id]);
                    }, ])
                ->rawColumns(['final_total', 'action', 'total_amount_recovered'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.index')
            ->with(compact('business_locations'));
    }

    /**
     * Resets all current stock to zero for a selected business location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetLocationStock(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.reset')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'location_id' => 'required|integer',
            'confirm_text' => 'required|in:RESET',
            'admin_password' => 'required|string',
        ]);

        if (! Hash::check($request->input('admin_password'), auth()->user()->password)) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('stock_adjustment.invalid_admin_password'),
            ]);
        }

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            $location_id = $request->input('location_id');

            $location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->firstOrFail();

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all' && ! in_array($location->id, $permitted_locations)) {
                abort(403, 'Unauthorized action.');
            }

            $stocks = DB::table('variation_location_details as vld')
                ->join('products as p', 'p.id', '=', 'vld.product_id')
                ->join('variations as v', 'v.id', '=', 'vld.variation_id')
                ->where('p.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->where('vld.location_id', $location->id)
                ->where('vld.qty_available', '>', 0)
                ->select(
                    'vld.product_id',
                    'vld.variation_id',
                    'vld.qty_available',
                    DB::raw('COALESCE(v.dpp_inc_tax, v.default_purchase_price, 0) as unit_price')
                )
                ->get();

            if ($stocks->isEmpty()) {
                DB::rollBack();

                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('stock_adjustment.no_stock_to_reset'),
                ]);
            }

            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            $ref_no = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);

            $adjustment_lines = [];
            $final_total = 0;
            foreach ($stocks as $stock) {
                $quantity = $this->productUtil->num_uf($stock->qty_available);
                $unit_price = $this->productUtil->num_uf($stock->unit_price);
                $final_total += $quantity * $unit_price;

                $adjustment_lines[] = [
                    'product_id' => $stock->product_id,
                    'variation_id' => $stock->variation_id,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                ];

                $this->productUtil->decreaseProductQuantity(
                    $stock->product_id,
                    $stock->variation_id,
                    $location->id,
                    $quantity
                );
            }

            $stock_adjustment = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location->id,
                'type' => 'stock_adjustment',
                'status' => 'final',
                'adjustment_type' => 'normal',
                'ref_no' => $ref_no,
                'transaction_date' => \Carbon::now(),
                'total_amount_recovered' => 0,
                'final_total' => $final_total,
                'additional_notes' => __('stock_adjustment.stock_reset_note', ['location' => $location->name]),
                'created_by' => $user_id,
            ]);

            $stock_adjustment->stock_adjustment_lines()->createMany($adjustment_lines);

            $business = [
                'id' => $business_id,
                'accounting_method' => $request->session()->get('business.accounting_method'),
                'location_id' => $location->id,
            ];
            $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');

            event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'added'));

            $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);

            DB::commit();

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => __('stock_adjustment.stock_reset_successfully'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockAdjustmentController::class, 'index']));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.create')
                ->with(compact('business_locations'));
    }

    /** Show the physical stock count screen. */
    public function createStocktake()
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([self::class, 'index']));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.stocktake')->with(compact('business_locations'));
    }

    /** Show reorder-level products which can be transferred to a new LPO. */
    public function stockAlertLpo()
    {
        if (! auth()->user()->can('stock_adjustment.create') || ! auth()->user()->can('purchase_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.stock_alert_lpo', compact('business_locations'));
    }

    /** DataTable source for the LPO stock-alert selector. */
    public function stockAlertLpoItems(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.create') || ! auth()->user()->can('purchase_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $products = $this->productUtil->getProductAlert($business_id, auth()->user()->permitted_locations());
        if (empty($request->input('location_id'))) {
            $products->whereRaw('1 = 0');
        } else {
            abort_unless(BusinessLocation::where('business_id', $business_id)->where('id', $request->input('location_id'))->exists(), 422, 'Invalid business location.');
        }

        return Datatables::of($products)
            ->addColumn('select_item', function ($row) {
                return '<input type="checkbox" class="lpo-alert-select" value="'.(int) $row->variation_id.'">';
            })
            ->editColumn('product', function ($row) {
                return e($row->type === 'single'
                    ? $row->product.' ('.$row->sku.')'
                    : $row->product.' - '.$row->product_variation.' - '.$row->variation.' ('.$row->sub_sku.')');
            })
            ->editColumn('stock', fn ($row) => '<span data-is_quantity="true" data-orig-value="'.(float) $row->stock.'" class="display_currency" data-currency_symbol="false">'.(float) $row->stock.'</span> '.e($row->unit))
            ->editColumn('alert_quantity', fn ($row) => '<span data-is_quantity="true" data-orig-value="'.(float) $row->alert_quantity.'" class="display_currency" data-currency_symbol="false">'.(float) $row->alert_quantity.'</span> '.e($row->unit))
            ->addColumn('order_quantity', function ($row) {
                $suggested = max(1, (float) $row->alert_quantity - (float) $row->stock);
                return '<input type="text" class="form-control input-sm input_number lpo-order-quantity" value="'.$this->productUtil->num_f($suggested, false, null, true).'" data-variation-id="'.(int) $row->variation_id.'">';
            })
            ->rawColumns(['select_item', 'stock', 'alert_quantity', 'order_quantity'])
            ->make(true);
    }

    /** Validate selected alerts and carry them to the standard Purchase Order form. */
    public function prepareStockAlertLpo(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.create') || ! auth()->user()->can('purchase_order.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'location_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.variation_id' => 'required|integer|distinct',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);
        $business_id = $request->session()->get('user.business_id');
        $location_id = (int) $request->input('location_id');
        abort_unless(BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->exists(), 422, 'Invalid business location.');

        $permitted = auth()->user()->permitted_locations();
        abort_if($permitted !== 'all' && ! in_array($location_id, array_map('intval', $permitted), true), 403, 'Unauthorized location.');

        $variation_ids = collect($request->input('items'))->pluck('variation_id')->map(fn ($id) => (int) $id)->all();
        $valid = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->join('variation_location_details as vld', function ($join) use ($location_id) {
                $join->on('vld.variation_id', '=', 'v.id')->where('vld.location_id', '=', $location_id);
            })
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereIn('v.id', $variation_ids)
            ->whereNotNull('p.alert_quantity')
            ->whereColumn('vld.qty_available', '<=', 'p.alert_quantity')
            ->select('v.id as variation_id', 'p.id as product_id')
            ->get()->keyBy('variation_id');

        $items = collect($request->input('items'))->map(function ($item) use ($valid) {
            $product = $valid->get((int) $item['variation_id']);
            abort_if(empty($product), 422, 'One or more selected products are no longer at reorder level. Refresh the list and try again.');
            return ['product_id' => (int) $product->product_id, 'variation_id' => (int) $product->variation_id, 'quantity' => (float) $item['quantity']];
        })->values()->all();

        $request->session()->put('stock_alert_lpo_prefill', ['location_id' => $location_id, 'items' => $items]);

        return redirect()->action([\App\Http\Controllers\PurchaseOrderController::class, 'create']);
    }

    /** Return a row for the physical stock count table. */
    public function getStocktakeProductRow(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'row_index' => 'required|integer|min:0',
            'variation_id' => 'required|integer',
            'location_id' => 'required|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');
        abort_unless(
            BusinessLocation::where('business_id', $business_id)->where('id', $request->input('location_id'))->exists(),
            422,
            'Invalid business location.'
        );
        $product = $this->productUtil->getDetailsFromVariation(
            $request->input('variation_id'),
            $business_id,
            null,
            false,
            true
        );
        abort_unless((int) $product->enable_stock === 1, 422, __('lang_v1.stock_not_enabled'));
        $product->qty_available = (float) DB::table('variation_location_details')
            ->where('variation_id', $request->input('variation_id'))
            ->where('product_id', $product->product_id)
            ->where('location_id', $request->input('location_id'))
            ->value('qty_available');
        $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
        $row_index = $request->input('row_index');

        return view('stock_adjustment.partials.stocktake_product_row', compact('product', 'row_index'));
    }

    /** Apply one stocktake, splitting shortages and surpluses into auditable adjustments. */
    public function storeStocktake(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'location_id' => 'required|integer',
            'transaction_date' => 'required|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.variation_id' => 'required|integer',
            'products.*.physical_quantity' => 'required|numeric|min:0',
        ]);

        $business_id = $request->session()->get('user.business_id');
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([self::class, 'index']));
        }

        try {
            DB::beginTransaction();
            $location_id = (int) $request->input('location_id');
            abort_unless(BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->exists(), 422, 'Invalid business location.');

            $changes = ['increase' => [], 'decrease' => []];
            foreach ($request->input('products') as $line) {
                $stock = DB::table('variations as v')
                    ->join('products as p', 'p.id', '=', 'v.product_id')
                    ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                        $join->on('vld.variation_id', '=', 'v.id')->where('vld.location_id', '=', $location_id);
                    })
                    ->where('p.business_id', $business_id)
                    ->where('p.enable_stock', 1)
                    ->where('p.id', $line['product_id'])
                    ->where('v.id', $line['variation_id'])
                    ->select('p.id as product_id', 'v.id as variation_id', 'v.dpp_inc_tax', DB::raw('COALESCE(vld.qty_available, 0) as qty_available'))
                    ->lockForUpdate()
                    ->first();

                if (empty($stock)) {
                    throw new \InvalidArgumentException('A stocktake product is invalid or does not track stock.');
                }

                $physical = $this->productUtil->num_uf($line['physical_quantity']);
                $difference = round($physical - (float) $stock->qty_available, 4);
                if ($difference == 0) {
                    continue;
                }

                $direction = $difference > 0 ? 'increase' : 'decrease';
                $quantity = abs($difference);
                $changes[$direction][] = [
                    'product_id' => $stock->product_id,
                    'variation_id' => $stock->variation_id,
                    'quantity' => $quantity,
                    'unit_price' => (float) $stock->dpp_inc_tax,
                ];

                if ($direction === 'increase') {
                    $this->productUtil->increaseProductQuantity($stock->product_id, $stock->variation_id, $location_id, $quantity);
                } else {
                    $this->productUtil->decreaseProductQuantity($stock->product_id, $stock->variation_id, $location_id, $quantity);
                }
            }

            $created = 0;
            foreach ($changes as $direction => $lines) {
                if (empty($lines)) {
                    continue;
                }
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
                $ref_no = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
                $transaction = Transaction::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'stock_adjustment',
                    'status' => 'final',
                    'transaction_date' => $this->productUtil->uf_date($request->input('transaction_date'), true),
                    'adjustment_type' => 'normal',
                    'stock_adjustment_direction' => $direction,
                    'ref_no' => $ref_no,
                    'final_total' => collect($lines)->sum(fn ($line) => $line['quantity'] * $line['unit_price']),
                    'total_amount_recovered' => 0,
                    'additional_notes' => trim(__('stock_adjustment.stocktake_reference_note', ['reference' => $request->input('stocktake_reference') ?: $ref_no]).' '.$request->input('additional_notes')),
                    'created_by' => $request->session()->get('user.id'),
                ]);
                $transaction->stock_adjustment_lines()->createMany($lines);

                if ($direction === 'decrease') {
                    $business = ['id' => $business_id, 'accounting_method' => $request->session()->get('business.accounting_method'), 'location_id' => $location_id];
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->stock_adjustment_lines, 'stock_adjustment');
                }
                event(new StockAdjustmentCreatedOrModified($transaction, 'added'));
                $this->transactionUtil->activityLog($transaction, 'added', null, [], false);
                $created++;
            }

            DB::commit();
            $output = ['success' => 1, 'msg' => $created ? __('stock_adjustment.stocktake_saved_successfully') : __('stock_adjustment.stocktake_no_changes')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => $e instanceof \App\Exceptions\PurchaseSellMismatch ? $e->getMessage() : __('messages.something_went_wrong')];
        }

        return redirect()->route('stock-adjustments.stocktake.create')->with('status', $output);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('stock_adjustment.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input_data = $request->only(['location_id', 'transaction_date', 'adjustment_type', 'stock_adjustment_direction', 'additional_notes', 'total_amount_recovered', 'final_total', 'ref_no']);
            $input_data['stock_adjustment_direction'] = in_array($input_data['stock_adjustment_direction'] ?? null, ['increase', 'decrease'], true)
                ? $input_data['stock_adjustment_direction']
                : 'decrease';
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\StockAdjustmentController::class, 'index']));
            }

            $user_id = $request->session()->get('user.id');

            $input_data['type'] = 'stock_adjustment';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
            }

            $products = $request->input('products');

            if (! empty($products)) {
                $product_data = [];

                foreach ($products as $product) {
                    $adjustment_line = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'unit_price' => $this->productUtil->num_uf($product['unit_price']),
                    ];
                    if (! empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to stock adjustment line
                        $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
                    }
                    $product_data[] = $adjustment_line;

                    $quantity = $this->productUtil->num_uf($product['quantity']);
                    if ($input_data['stock_adjustment_direction'] === 'increase') {
                        $this->productUtil->increaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $quantity
                        );
                    } else {
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $quantity
                        );
                    }
                }

                $stock_adjustment = Transaction::create($input_data);
                $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

                // Only decreases consume existing purchase stock layers.
                if ($input_data['stock_adjustment_direction'] === 'decrease') {
                    $business = ['id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input_data['location_id'],
                    ];
                    $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');
                }

                event(new StockAdjustmentCreatedOrModified($stock_adjustment, 'added'));

                $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);
            }

            $output = ['success' => 1,
                'msg' => __('stock_adjustment.stock_adjustment_added_successfully'),
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
        }

        return redirect('stock-adjustments')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('stock_adjustment.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $stock_adjustment = Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.id', $id)
                    ->where('transactions.type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines', 'location', 'business', 'stock_adjustment_lines.variation', 'stock_adjustment_lines.variation.product', 'stock_adjustment_lines.variation.product_variation', 'stock_adjustment_lines.lot_details'])
                    ->first();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $activities = Activity::forSubject($stock_adjustment)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('stock_adjustment.show')
                ->with(compact('stock_adjustment', 'lot_n_exp_enabled', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('stock_adjustment.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                DB::beginTransaction();

                $stock_adjustment = Transaction::where('id', $id)
                                    ->where('type', 'stock_adjustment')
                                    ->with(['stock_adjustment_lines'])
                                    ->first();

                //Add deleted product quantity to available quantity
                $stock_adjustment_lines = $stock_adjustment->stock_adjustment_lines;
                if (! empty($stock_adjustment_lines)) {
                    $line_ids = [];
                    foreach ($stock_adjustment_lines as $stock_adjustment_line) {
                        $quantity = $this->productUtil->num_f($stock_adjustment_line->quantity);
                        if (($stock_adjustment->stock_adjustment_direction ?? 'decrease') === 'increase') {
                            $this->productUtil->decreaseProductQuantity(
                                $stock_adjustment_line->product_id,
                                $stock_adjustment_line->variation_id,
                                $stock_adjustment->location_id,
                                $quantity
                            );
                        } else {
                            $this->productUtil->updateProductQuantity(
                                $stock_adjustment->location_id,
                                $stock_adjustment_line->product_id,
                                $stock_adjustment_line->variation_id,
                                $quantity
                            );
                        }
                        $line_ids[] = $stock_adjustment_line->id;
                    }

                    if (($stock_adjustment->stock_adjustment_direction ?? 'decrease') === 'decrease') {
                        $this->transactionUtil->mapPurchaseQuantityForDeleteStockAdjustment($line_ids);
                    }
                }
                $stock_adjustment->delete();

                event( new StockAdjustmentCreatedOrModified($stock_adjustment, 'deleted'));


                //Remove Mapping between stock adjustment & purchase.

                $output = ['success' => 1,
                    'msg' => __('stock_adjustment.delete_success'),
                ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Return product rows
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductRow(Request $request)
    {
        if (request()->ajax()) {
            $row_index = $request->input('row_index');
            $variation_id = $request->input('variation_id');
            $location_id = $request->input('location_id');

            $business_id = $request->session()->get('user.business_id');
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $type = ! empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);
            if ($type == 'stock_transfer') {
                return view('stock_transfer.partials.product_table_row')
                    ->with(compact('product', 'row_index', 'sub_units'));
            } else {
                return view('stock_adjustment.partials.product_table_row')
                        ->with(compact('product', 'row_index', 'sub_units'));
            }
        }
    }

    /**
     * Sets expired purchase line as stock adjustmnet
     *
     * @param  int  $purchase_line_id
     * @return json $output
     */
    public function removeExpiredStock($purchase_line_id)
    {
        if (! auth()->user()->can('stock_adjustment.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $purchase_line = PurchaseLine::where('id', $purchase_line_id)
                                    ->with(['transaction'])
                                    ->first();

            if (! empty($purchase_line)) {
                DB::beginTransaction();

                $qty_unsold = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted - $purchase_line->quantity_returned;
                $final_total = $purchase_line->purchase_price_inc_tax * $qty_unsold;

                $user_id = request()->session()->get('user.id');
                $business_id = request()->session()->get('user.business_id');

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');

                $stock_adjstmt_data = [
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'transaction_date' => \Carbon::now()->format('Y-m-d'),
                    'total_amount_recovered' => 0,
                    'location_id' => $purchase_line->transaction->location_id,
                    'adjustment_type' => 'normal',
                    'final_total' => $final_total,
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count),
                ];

                //Create stock adjustment transaction
                $stock_adjustment = Transaction::create($stock_adjstmt_data);

                $stock_adjustment_line = [
                    'product_id' => $purchase_line->product_id,
                    'variation_id' => $purchase_line->variation_id,
                    'quantity' => $qty_unsold,
                    'unit_price' => $purchase_line->purchase_price_inc_tax,
                    'removed_purchase_line' => $purchase_line->id,
                ];

                //Create stock adjustment line with the purchase line
                $stock_adjustment->stock_adjustment_lines()->create($stock_adjustment_line);

                //Decrease available quantity
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase_line->transaction->location_id,
                    $qty_unsold
                );

                //Map Stock adjustment & Purchase.
                $business = ['id' => $business_id,
                    'accounting_method' => request()->session()->get('business.accounting_method'),
                    'location_id' => $purchase_line->transaction->location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment', false, $purchase_line->id);

                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_removed_successfully'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
        }

        return $output;
    }
}

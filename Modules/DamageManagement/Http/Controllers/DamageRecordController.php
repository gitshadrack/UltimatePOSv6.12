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
use App\Contact;
use App\Product;
use App\Unit;
use App\Variation;
use App\Brands;
use App\Category;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Modules\DamageManagement\Entities\DamageRecord;
use Carbon\Carbon;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DamageRecordController extends Controller
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

    public function stockReport(Request $request)
    {
        if (!auth()->user()->can('damage_record.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');

        // Get filter parameters
        $location_id = $request->get('location_id');
        $brand_id = $request->get('brand_id');
        $category_id = $request->get('category_id');
        $date_range = $request->get('date_range');
        
        // Parse date range
        $start_date = null;
        $end_date = null;
        if ($date_range) {
            $dates = explode('~', $date_range);
            if (count($dates) == 2) {
                $start_date = trim($dates[0]);
                $end_date = trim($dates[1]);
            }
        }

        // Get filter options for view
        $permittedLocations = auth()->user()->permitted_locations();
        $brands = Brands::forDropdown($businessId, false, false);
        $categories = Category::forDropdown($businessId, 'product');
        
        // Get business locations
        $query = BusinessLocation::where('business_id', $businessId);
        
        if ($permittedLocations != 'all') {
            $query->whereIn('id', $permittedLocations);
        }
        
        $locations_array = $query->select('id', 'name')->get();
        $locations = ['0' => __('lang_v1.all')];
        foreach ($locations_array as $location) {
            $locations[$location->id] = $location->name;
        }

        // Get all damage records with product details
        $records = DamageRecord::leftJoin('products as P', 'damage_records.product_id', '=', 'P.id')
            ->leftJoin('variations as V', 'damage_records.variation_id', '=', 'V.id')
            ->leftJoin('brands as B', 'damage_records.brand_id', '=', 'B.id')
            ->leftJoin('business_locations as BL', 'damage_records.location_id', '=', 'BL.id')
            ->where('damage_records.business_id', $businessId);
        
        // Apply filters
        if (!empty($location_id)) {
            $records->where('damage_records.location_id', $location_id);
        }
        
        if (!empty($brand_id)) {
            $records->where('damage_records.brand_id', $brand_id);
        }
        
        if (!empty($category_id)) {
            $records->where('P.category_id', $category_id);
        }
        
        if (!empty($start_date) && !empty($end_date)) {
            $records->whereBetween('damage_records.reported_at', [$start_date, $end_date . ' 23:59:59']);
        }
        
        $records = $records->select(
                'damage_records.id',
                'damage_records.product_id',
                'damage_records.variation_id',
                'damage_records.location_id',
                'P.name as product_name',
                'V.sub_sku',
                'B.name as brand_name',
                'BL.name as location_name',
                DB::raw('SUM(damage_records.quantity) as total_quantity'),
                DB::raw('SUM(damage_records.dispatched_quantity) as total_dispatched_quantity')
            )
            ->groupBy(
                'damage_records.product_id',
                'damage_records.variation_id',
                'damage_records.location_id',
                'P.name',
                'V.sub_sku',
                'B.name',
                'BL.name'
            )
            ->orderBy('P.name')
            ->orderBy('V.sub_sku')
            ->get();

        // Get current stock for each product
        foreach ($records as $record) {
            $current_stock = $this->productUtil->getCurrentStock(
                $record->variation_id,
                $record->location_id
            );
            
            $record->current_stock = $current_stock;
            $record->closing_stock = $current_stock - $record->total_dispatched_quantity;
        }

        return view('damagemanagement::record.stock_report', compact(
            'records',
            'locations',
            'brands',
            'categories',
            'location_id',
            'brand_id',
            'category_id',
            'date_range'
        ));
    }

    public function dashboard()
    {
        if (!auth()->user()->can('damage_record.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        
        // Get basic statistics
        $totalRecords = DamageRecord::where('business_id', $businessId)->count();
        $pendingRecords = DamageRecord::where('business_id', $businessId)
            ->where('approval_status', 'pending')
            ->count();
        $approvedRecords = DamageRecord::where('business_id', $businessId)
            ->where('approval_status', 'approved')
            ->count();
        $rejectedRecords = DamageRecord::where('business_id', $businessId)
            ->where('approval_status', 'rejected')
            ->count();
        
        // Get financial statistics
        $totalPurchaseValue = DamageRecord::where('business_id', $businessId)->sum('purchase_value');
        $totalSellValue = DamageRecord::where('business_id', $businessId)->sum('sell_value');
        $totalExpectedCompensation = DamageRecord::where('business_id', $businessId)->sum('expected_compensation');
        $totalGivenCompensation = DamageRecord::where('business_id', $businessId)->sum('given_compensation');
        
        // Get quantity statistics
        $totalQuantity = DamageRecord::where('business_id', $businessId)->sum('quantity');
        $totalDispatchedQuantity = DamageRecord::where('business_id', $businessId)->sum('dispatched_quantity');
        
        // Get unique products count
        $uniqueProducts = DamageRecord::where('business_id', $businessId)
            ->distinct('product_id')
            ->count('product_id');
        
        // Get monthly damage records for chart (last 12 months)
        $monthlyData = DamageRecord::where('business_id', $businessId)
            ->where('reported_at', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(reported_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Get damage records by dispatch status for chart
        $dispatchStatusData = DamageRecord::where('business_id', $businessId)
            ->select('dispatch_status', DB::raw('COUNT(*) as count'))
            ->groupBy('dispatch_status')
            ->get();
        
        // Get approval status breakdown
        $approvalStatusData = DamageRecord::where('business_id', $businessId)
            ->select('approval_status', DB::raw('COUNT(*) as count'))
            ->groupBy('approval_status')
            ->get();
        
        // Get damage records by location
        $locationData = DamageRecord::leftJoin('business_locations as BL', 'damage_records.location_id', '=', 'BL.id')
            ->where('damage_records.business_id', $businessId)
            ->select('BL.name as location_name', DB::raw('COUNT(*) as count'))
            ->groupBy('BL.name', 'damage_records.location_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        // Get recent damage records
        $recentRecords = DamageRecord::leftJoin('products as P', 'damage_records.product_id', '=', 'P.id')
            ->leftJoin('business_locations as BL', 'damage_records.location_id', '=', 'BL.id')
            ->where('damage_records.business_id', $businessId)
            ->select(
                'damage_records.id',
                'damage_records.reference_no',
                'damage_records.reported_at',
                'damage_records.quantity',
                'damage_records.dispatch_status',
                'damage_records.approval_status',
                'P.name as product_name',
                'BL.name as location_name'
            )
            ->orderBy('damage_records.reported_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get top dispatched products (by quantity)
        $topDispatchedProducts = DamageRecord::leftJoin('products as P', 'damage_records.product_id', '=', 'P.id')
            ->leftJoin('variations as V', 'damage_records.variation_id', '=', 'V.id')
            ->leftJoin('brands as B', 'damage_records.brand_id', '=', 'B.id')
            ->where('damage_records.business_id', $businessId)
            ->where('damage_records.dispatched_quantity', '>', 0)
            ->select(
                'P.name as product_name',
                'V.sub_sku',
                'B.name as brand_name',
                DB::raw('SUM(damage_records.dispatched_quantity) as total_dispatched_quantity'),
                DB::raw('SUM(damage_records.purchase_value) as total_purchase_value'),
                DB::raw('SUM(damage_records.sell_value) as total_sell_value')
            )
            ->groupBy('damage_records.product_id', 'damage_records.variation_id', 'P.name', 'V.sub_sku', 'B.name')
            ->orderBy('total_dispatched_quantity', 'desc')
            ->limit(10)
            ->get();
        
        return view('damagemanagement::record.dashboard', compact(
            'totalRecords',
            'pendingRecords',
            'approvedRecords',
            'rejectedRecords',
            'totalPurchaseValue',
            'totalSellValue',
            'totalExpectedCompensation',
            'totalGivenCompensation',
            'totalQuantity',
            'totalDispatchedQuantity',
            'uniqueProducts',
            'monthlyData',
            'dispatchStatusData',
            'approvalStatusData',
            'locationData',
            'recentRecords',
            'topDispatchedProducts'
        ));
    }

    public function index()
    {
        if (!auth()->user()->can('damage_record.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $businessId = request()->session()->get('user.business_id');

            $damageRecords = DamageRecord::leftJoin('products as P', 'damage_records.product_id', '=', 'P.id')
                ->leftJoin('variations as V', 'damage_records.variation_id', '=', 'V.id')
                ->leftJoin('brands as B', 'damage_records.brand_id', '=', 'B.id')
                ->leftJoin('categories as CAT', 'damage_records.category_id', '=', 'CAT.id')
                ->leftJoin('units as U', 'damage_records.unit_id', '=', 'U.id')
                ->leftJoin('contacts as CUST', 'damage_records.customer_id', '=', 'CUST.id')
                ->leftJoin('contacts as SUP', 'damage_records.supplier_id', '=', 'SUP.id')
                ->leftJoin('business_locations as BL', 'damage_records.location_id', '=', 'BL.id')
                ->where('damage_records.business_id', $businessId)
                ->select(
                    'damage_records.id',
                    'damage_records.reference_no',
                    'damage_records.reported_at',
                    'damage_records.quantity',
                    'damage_records.dispatched_quantity',
                    'damage_records.purchase_value',
                    'damage_records.sell_value',
                    'damage_records.expected_compensation',
                    'damage_records.given_compensation',
                    'damage_records.dispatch_status',
                    'damage_records.approval_status',
                    'damage_records.approved_by',
                    'damage_records.approved_at',
                    'damage_records.notes',
                    'P.name as product_name',
                    'P.sku as product_sku',
                    'V.sub_sku',
                    'B.name as brand_name',
                    'CAT.name as category_name',
                    'U.short_name as unit_name',
                    'BL.name as location_name',
                    DB::raw("TRIM(CONCAT_WS(' / ', NULLIF(CUST.supplier_business_name, ''), NULLIF(CUST.name, ''))) as customer_name"),
                    DB::raw("TRIM(CONCAT_WS(' / ', NULLIF(SUP.supplier_business_name, ''), NULLIF(SUP.name, ''))) as supplier_name")
                );

            $permittedLocations = auth()->user()->permitted_locations();
            if ($permittedLocations != 'all') {
                $damageRecords->whereIn('damage_records.location_id', $permittedLocations);
            }

            if ($locationId = request()->get('location_id')) {
                $damageRecords->where('damage_records.location_id', $locationId);
            }

            if ($brandId = request()->get('brand_id')) {
                $damageRecords->where('damage_records.brand_id', $brandId);
            }

            if ($categoryId = request()->get('category_id')) {
                $damageRecords->where('damage_records.category_id', $categoryId);
            }

            if ($customerId = request()->get('customer_id')) {
                $damageRecords->where('damage_records.customer_id', $customerId);
            }

            if ($supplierId = request()->get('supplier_id')) {
                $damageRecords->where('damage_records.supplier_id', $supplierId);
            }

            if ($productId = request()->get('product_id')) {
                $damageRecords->where('damage_records.product_id', $productId);
            }

            if ($status = request()->get('dispatch_status')) {
                $damageRecords->where('damage_records.dispatch_status', $status);
            }

            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            if (!empty($startDate) && !empty($endDate)) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $damageRecords->whereBetween('damage_records.reported_at', [$start, $end]);
            }

            return Datatables::of($damageRecords)
                ->editColumn('reported_at', '{{@format_datetime($reported_at)}}')
                ->editColumn('quantity', function ($row) {
                    return $this->transactionUtil->num_f($row->quantity, false, null, true);
                })
                ->addColumn('dispatched_qty', function ($row) {
                    $dispatched = (float) $row->dispatched_quantity;

                    return $this->transactionUtil->num_f($dispatched, false, null, true);
                })
                ->addColumn('remaining_qty', function ($row) {
                    $remaining = (float) $row->quantity - (float) $row->dispatched_quantity;

                    return $this->transactionUtil->num_f(max($remaining, 0), false, null, true);
                })
                ->editColumn('purchase_value', function ($row) {
                    // Calculate value based on dispatched quantity
                    if ($row->quantity > 0 && $row->dispatched_quantity > 0) {
                        $unitPurchasePrice = (float) $row->purchase_value / (float) $row->quantity;
                        $dispatchedPurchaseValue = $row->dispatched_quantity * $unitPurchasePrice;
                        return $this->transactionUtil->num_f($dispatchedPurchaseValue, true);
                    }
                    return $this->transactionUtil->num_f($row->purchase_value, true);
                })
                ->editColumn('sell_value', function ($row) {
                    // Calculate value based on dispatched quantity
                    if ($row->quantity > 0 && $row->dispatched_quantity > 0) {
                        $unitSellPrice = (float) $row->sell_value / (float) $row->quantity;
                        $dispatchedSellValue = $row->dispatched_quantity * $unitSellPrice;
                        return $this->transactionUtil->num_f($dispatchedSellValue, true);
                    }
                    return $this->transactionUtil->num_f($row->sell_value, true);
                })
                ->editColumn('expected_compensation', function ($row) {
                    // Calculate compensation based on dispatched quantity
                    if ($row->quantity > 0 && $row->dispatched_quantity > 0) {
                        $unitCompensation = (float) $row->expected_compensation / (float) $row->quantity;
                        $dispatchedCompensation = $row->dispatched_quantity * $unitCompensation;
                        return $this->transactionUtil->num_f($dispatchedCompensation, true);
                    }
                    return $this->transactionUtil->num_f($row->expected_compensation, true);
                })
                ->editColumn('given_compensation', function ($row) {
                    return $this->transactionUtil->num_f($row->given_compensation, true);
                })
                ->addColumn('dispatch_status_label', function ($row) {
                    $badgeClass = '';
                    $statusKey = 'damagemanagement::damage.dispatch_status_'.$row->dispatch_status;
                    
                    switch($row->dispatch_status) {
                        case 'not_dispatched':
                            $badgeClass = 'warning';
                            break;
                        case 'partial':
                            $badgeClass = 'info';
                            break;
                        case 'dispatched':
                            $badgeClass = 'success';
                            break;
                        case 'full_dispatch':
                            $badgeClass = 'primary';
                            break;
                    }
                    
                    $html = '<span class="label label-'.$badgeClass.'">'.__($statusKey).'</span>';
                    
                    return $html;
                })
                ->addColumn('status_change', function ($row) {
                    if (!auth()->user()->can('damage_record.update') || $row->dispatch_status == 'dispatched') {
                        return '';
                    }
                    
                    $html = '<select class="form-control input-sm change-dispatch-status" data-id="'.$row->id.'" style="width: 120px;">';
                    $html .= '<option value="not_dispatched" '.($row->dispatch_status == 'not_dispatched' ? 'selected' : '').'>Not Dispatched</option>';
                    $html .= '<option value="partial" '.($row->dispatch_status == 'partial' ? 'selected' : '').'>Partial</option>';
                    $html .= '</select>';
                    
                    return $html;
                })
                ->addColumn('approval_status_label', function ($row) {
                    $badgeClass = '';
                    $statusKey = 'damagemanagement::damage.approval_status_'.($row->approval_status ?? 'pending');
                    
                    switch($row->approval_status ?? 'pending') {
                        case 'pending':
                            $badgeClass = 'warning';
                            break;
                        case 'approved':
                            $badgeClass = 'success';
                            break;
                        case 'rejected':
                            $badgeClass = 'danger';
                            break;
                        default:
                            $badgeClass = 'warning';
                    }
                    
                    $html = '<span class="label label-'.$badgeClass.'">'.__($statusKey).'</span>';
                    
                    return $html;
                })
                ->addColumn('approval_status_change', function ($row) {
                    if (!auth()->user()->can('damage_record.update')) {
                        return '';
                    }
                    
                    $html = '<select class="form-control input-sm change-approval-status" data-id="'.$row->id.'" style="width: 100px;">';
                    $html .= '<option value="pending" '.($row->approval_status == 'pending' ? 'selected' : '').'>Pending</option>';
                    $html .= '<option value="approved" '.($row->approval_status == 'approved' ? 'selected' : '').'>Approved</option>';
                    $html .= '<option value="rejected" '.($row->approval_status == 'rejected' ? 'selected' : '').'>Rejected</option>';
                    $html .= '</select>';
                    
                    return $html;
                })
                ->addColumn('product_display', function ($row) {
                    return $row->product_name.' ('.$row->sub_sku.')';
                })
                ->addColumn('action', function ($row) {
                    // Create dropdown menu
                    $dropdown = '<div class="btn-group">';
                    $dropdown .= '<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $dropdown .= '<i class="fa fa-bars"></i>';
                    $dropdown .= '</button>';
                    $dropdown .= '<ul class="dropdown-menu">';
                    
                    // View option
                    if (auth()->user()->can('damage_record.view')) {
                        $dropdown .= '<li><a href="#" class="btn-modal" data-href="'.action([self::class, 'show'], [$row->id]).'" data-container=".view_modal"><i class="fa fa-eye"></i> '.__('messages.view').'</a></li>';
                    }
                    
                    // Edit option
                    if (auth()->user()->can('damage_record.update')) {
                        $dropdown .= '<li><a href="#" class="btn-modal" data-href="'.action([self::class, 'edit'], [$row->id]).'" data-container=".view_modal"><i class="fa fa-edit"></i> '.__('messages.edit').'</a></li>';
                        $dropdown .= '<li role="separator" class="divider"></li>';
                        
                        // Dispatch status options
                        $dropdown .= '<li class="dropdown-header">'.__('damagemanagement::damage.dispatch_status').'</li>';
                        if ($row->dispatch_status != 'dispatched') {
                            $dropdown .= '<li><a href="#" class="change-dispatch-status-item" data-id="'.$row->id.'" data-status="not_dispatched"><i class="fa fa-dot-circle-o"></i> '.__('damagemanagement::damage.dispatch_status_not_dispatched').'</a></li>';
                            $dropdown .= '<li><a href="#" class="change-dispatch-status-item" data-id="'.$row->id.'" data-status="partial"><i class="fa fa-dot-circle-o"></i> '.__('damagemanagement::damage.dispatch_status_partial').'</a></li>';
                        }
                        
                        // Approval status options
                        $dropdown .= '<li role="separator" class="divider"></li>';
                        $dropdown .= '<li class="dropdown-header">'.__('damagemanagement::damage.approval_status').'</li>';
                        $dropdown .= '<li><a href="#" class="change-approval-status-item" data-id="'.$row->id.'" data-status="pending"><i class="fa fa-dot-circle-o"></i> '.__('damagemanagement::damage.approval_status_pending').'</a></li>';
                        $dropdown .= '<li><a href="#" class="change-approval-status-item" data-id="'.$row->id.'" data-status="approved"><i class="fa fa-dot-circle-o"></i> '.__('damagemanagement::damage.approval_status_approved').'</a></li>';
                        $dropdown .= '<li><a href="#" class="change-approval-status-item" data-id="'.$row->id.'" data-status="rejected"><i class="fa fa-dot-circle-o"></i> '.__('damagemanagement::damage.approval_status_rejected').'</a></li>';
                    }
                    
                    // Delete option
                    if (auth()->user()->can('damage_record.delete')) {
                        $dropdown .= '<li role="separator" class="divider"></li>';
                        $dropdown .= '<li><a href="#" class="delete-damage-record text-red" data-href="'.action([self::class, 'destroy'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</a></li>';
                    }
                    
                    $dropdown .= '</ul>';
                    $dropdown .= '</div>';
                    
                    return $dropdown;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'dispatch_status_label', 'approval_status_label'])
                ->make(true);
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($businessId)->toArray();
        $brands = Brands::forDropdown($businessId, false)->toArray();
        $categories = Category::forDropdown($businessId, false)->toArray();
        $dispatchStatuses = [
            'not_dispatched' => __('damage.dispatch_status_not_dispatched'),
            'partial' => __('damage.dispatch_status_partial'),
            'dispatched' => __('damage.dispatch_status_dispatched'),
        ];

        return view('damagemanagement::record.index', compact(
            'locations',
            'brands',
            'categories',
            'dispatchStatuses'
        ));
    }

    public function create()
    {
        if (!auth()->user()->can('damage_record.create')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($businessId);
        $compensationBasis = [
            'purchase' => __('damagemanagement::damage.basis_purchase'),
            'sell' => __('damagemanagement::damage.basis_sell'),
            'manual' => __('damagemanagement::damage.basis_manual'),
        ];

        $asset_v = time();
        
        return view('damagemanagement::record.create', compact(
            'locations',
            'compensationBasis',
            'asset_v'
        ));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('damage_record.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $businessId = $request->session()->get('user.business_id');
            $userId = $request->session()->get('user.id');

            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.variation_id' => 'required|integer|exists:variations,id',
                'products.*.quantity' => 'required',
                'products.*.unit_purchase_price' => 'nullable',
                'products.*.unit_sell_price' => 'nullable',
                'products.*.expected_compensation' => 'nullable',
                'location_id' => 'nullable|integer|exists:business_locations,id',
                'reported_at' => 'required|string',
                'reference_no' => 'nullable|string|max:191',
                'customer_id' => 'nullable|integer|exists:contacts,id',
                'supplier_id' => 'nullable|integer|exists:contacts,id',
                'compensation_basis' => 'required|in:purchase,sell,manual',
            ]);

            $reportedAt = $this->productUtil->uf_date($validated['reported_at'], true);
            $compBasis = $validated['compensation_basis'];
            $productsInput = $validated['products'];
            $recordsCreated = [];
            $referenceBase = $validated['reference_no'];
            $referenceDate = Carbon::parse($reportedAt);

            foreach ($productsInput as $index => $productRow) {
                $variationId = (int) $productRow['variation_id'];
                $variation = Variation::with(['product.brand', 'product.category', 'product.unit'])
                    ->whereHas('product', function ($query) use ($businessId) {
                        $query->where('business_id', $businessId);
                    })
                    ->findOrFail($variationId);

                $quantity = $this->productUtil->num_uf($productRow['quantity']);
                if ($quantity <= 0) {
                    throw new \Exception(__('damage.invalid_quantity'));
                }

                $unitPurchase = $this->productUtil->num_uf($productRow['unit_purchase_price'] ?? $variation->default_purchase_price);
                $unitSell = $this->productUtil->num_uf($productRow['unit_sell_price'] ?? $variation->default_sell_price);

                $purchaseValue = $quantity * $unitPurchase;
                $sellValue = $quantity * $unitSell;

                $expectedCompensation = $this->productUtil->num_uf($productRow['expected_compensation'] ?? 0);
                if ($compBasis === 'purchase') {
                    $expectedCompensation = $purchaseValue;
                } elseif ($compBasis === 'sell') {
                    $expectedCompensation = $sellValue;
                } elseif ($expectedCompensation <= 0) {
                    $expectedCompensation = $purchaseValue;
                }

                $reference = $referenceBase;
                if (empty($reference)) {
                    $reference = DamageRecord::generateReference($referenceDate, $businessId);
                } elseif (count($productsInput) > 1) {
                    $reference = $referenceBase . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                }

                $record = DamageRecord::create([
                    'business_id' => $businessId,
                    'location_id' => $validated['location_id'] ?? null,
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'brand_id' => $variation->product->brand_id,
                    'category_id' => $variation->product->category_id,
                    'unit_id' => $variation->product->unit_id,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'reference_no' => $reference,
                    'reported_at' => $reportedAt,
                    'quantity' => $quantity,
                    'unit_purchase_price' => $unitPurchase,
                    'unit_sell_price' => $unitSell,
                    'purchase_value' => $purchaseValue,
                    'sell_value' => $sellValue,
                    'expected_compensation' => $expectedCompensation,
                    'given_compensation' => 0,
                    'compensation_basis' => $compBasis,
                    'notes' => $request->input('notes'),
                    'created_by' => $userId,
                ]);

                $recordsCreated[] = $record->id;
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('damage.record_created'),
                'data' => ['records' => $recordsCreated],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage record store error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

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
        if (!auth()->user()->can('damage_record.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $record = DamageRecord::with([
                'product',
                'variation',
                'brand',
                'category',
                'unit',
                'location',
                'customer',
                'supplier',
                'dispatchLines.dispatch',
            ])
            ->forBusiness($businessId)
            ->findOrFail($id);

        // Check if this is an AJAX request for modal display
        $isAjax = request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest';
        
        if ($isAjax) {
            return response()->view('damagemanagement::record.show_modal', compact('record'));
        }

        return view('damagemanagement::record.show')->with(compact('record'));
    }

    public function print($id)
    {
        if (!auth()->user()->can('damage_record.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $record = DamageRecord::with([
                'product',
                'variation',
                'brand',
                'category',
                'unit',
                'location',
                'customer',
                'supplier',
                'dispatchLines.dispatch',
            ])
            ->forBusiness($businessId)
            ->findOrFail($id);

        return view('damagemanagement::record.print', compact('record'));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $record = DamageRecord::with(['product', 'variation', 'brand', 'category', 'unit', 'customer', 'supplier'])
            ->forBusiness($businessId)
            ->findOrFail($id);

        if ($record->dispatch_status === 'dispatched') {
            abort(403, __('damage.cannot_edit_dispatched'));
        }

        $locations = BusinessLocation::forDropdown($businessId);
        $compensationBasis = [
            'purchase' => __('damagemanagement::damage.basis_purchase'),
            'sell' => __('damagemanagement::damage.basis_sell'),
            'manual' => __('damagemanagement::damage.basis_manual'),
        ];

        $customerDisplay = $this->formatContactForDisplay($record->customer);
        $supplierDisplay = $this->formatContactForDisplay($record->supplier);
        $initialRowData = [
            'variation_id' => $record->variation_id,
            'product_id' => $record->product_id,
            'product_name' => $record->product->name . ' (' . $record->variation->sub_sku . ')',
            'default_purchase_price' => (float) $record->unit_purchase_price,
            'default_sell_price' => (float) $record->unit_sell_price,
            'brand_name' => optional($record->brand)->name,
            'category_name' => optional($record->category)->name,
            'unit_name' => optional($record->unit)->short_name,
            'quantity_precision' => optional($record->unit)->allow_decimal ? 2 : 0,
            'quantity' => (float) $record->quantity,
            'unit_purchase_price' => (float) $record->unit_purchase_price,
            'unit_sell_price' => (float) $record->unit_sell_price,
            'expected_compensation' => (float) $record->expected_compensation,
        ];

        // Check if this is an AJAX request for modal display
        $isAjax = request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest';
        
        if ($isAjax) {
            return response()->view('damagemanagement::record.edit_modal', compact(
                'record',
                'locations',
                'compensationBasis',
                'customerDisplay',
                'supplierDisplay',
                'initialRowData'
            ));
        }

        return view('damagemanagement::record.edit', compact(
            'record',
            'locations',
            'compensationBasis',
            'customerDisplay',
            'supplierDisplay',
            'initialRowData'
        ));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $businessId = $request->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);

            if ($record->dispatch_status === 'dispatched') {
                abort(403, __('damage.cannot_edit_dispatched'));
            }

            $validated = $request->validate([
                'location_id' => 'nullable|integer|exists:business_locations,id',
                'reported_at' => 'required|string',
                'quantity' => 'required',
                'unit_purchase_price' => 'nullable',
                'unit_sell_price' => 'nullable',
                'expected_compensation' => 'nullable',
                'reference_no' => 'nullable|string|max:191',
                'customer_id' => 'nullable|integer|exists:contacts,id',
                'supplier_id' => 'nullable|integer|exists:contacts,id',
                'compensation_basis' => 'required|in:purchase,sell,manual',
                'notes' => 'nullable|string',
            ]);

            $reportedAt = $this->productUtil->uf_date($validated['reported_at'], true);
            $quantity = $this->productUtil->num_uf($validated['quantity']);
            $unitPurchase = $this->productUtil->num_uf($request->input('unit_purchase_price', $record->unit_purchase_price));
            $unitSell = $this->productUtil->num_uf($request->input('unit_sell_price', $record->unit_sell_price));
            $expectedCompensation = $this->productUtil->num_uf($request->input('expected_compensation', $record->expected_compensation));

            if ($record->dispatched_quantity > $quantity) {
                throw new \Exception(__('damage.quantity_less_than_dispatched'));
            }

            $record->fill([
                'location_id' => $validated['location_id'] ?? null,
                'reference_no' => $validated['reference_no'] ?: $record->reference_no,
                'reported_at' => $reportedAt,
                'quantity' => $quantity,
                'unit_purchase_price' => $unitPurchase,
                'unit_sell_price' => $unitSell,
                'purchase_value' => $quantity * $unitPurchase,
                'sell_value' => $quantity * $unitSell,
                'expected_compensation' => $expectedCompensation ?: ($quantity * $unitPurchase),
                'compensation_basis' => $validated['compensation_basis'],
                'customer_id' => $validated['customer_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($record->dispatched_quantity == $record->quantity) {
                $record->dispatch_status = 'dispatched';
            } elseif ($record->dispatched_quantity > 0) {
                $record->dispatch_status = 'partial';
            } else {
                $record->dispatch_status = 'not_dispatched';
            }

            $record->save();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('damage.record_updated'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage record update error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

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

    public function destroy($id)
    {
        if (!auth()->user()->can('damage_record.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);

            if ($record->dispatch_status === 'dispatched') {
                throw new \Exception(__('damage.cannot_delete_dispatched'));
            }
            
            // Delete associated stock adjustment transaction if exists
            $refNo = $record->reference_no ? 'DMG-' . $record->reference_no : null;
            if ($refNo) {
                // Find transactions with this reference number
                $transactions = \App\Transaction::where('business_id', $businessId)
                    ->where('ref_no', $refNo)
                    ->where('type', 'stock_adjustment')
                    ->get();
                
                foreach ($transactions as $trans) {
                    // Delete stock adjustment lines first
                    \App\StockAdjustmentLine::where('transaction_id', $trans->id)->delete();
                    // Delete the transaction
                    \App\Transaction::where('id', $trans->id)->delete();
                }
            }

            $record->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('damage.record_deleted'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage record delete error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function searchContacts(Request $request)
    {
        if (!auth()->user()->can('damage_record.view') && !auth()->user()->can('damage_record.create')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        $type = $request->get('type', 'customer');
        $term = $request->get('term');

        $query = Contact::where('business_id', $businessId);
        if ($type === 'supplier') {
            $query->whereIn('type', ['supplier', 'both']);
        } else {
            $query->whereIn('type', ['customer', 'both']);
        }

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('supplier_business_name', 'like', '%'.$term.'%')
                    ->orWhere('contact_id', 'like', '%'.$term.'%')
                    ->orWhere('mobile', 'like', '%'.$term.'%');
            });
        }

        $results = $query
            ->select('id', 'name', 'supplier_business_name', 'contact_id', 'mobile')
            ->orderBy('supplier_business_name')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($contact) {
                $display = trim($contact->supplier_business_name ?: $contact->name ?: '');
                if ($display === '') {
                    $display = 'Contact #' . $contact->id;
                }

                $parts = array_filter([
                    $display,
                    $contact->contact_id ? '#'.$contact->contact_id : null,
                    $contact->mobile,
                ]);

                return [
                    'id' => $contact->id,
                    'text' => implode(' | ', $parts),
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    public function searchVariations(Request $request)
    {
        if (!auth()->user()->can('damage_record.create') && !auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        $term = $request->get('term');

        $query = Variation::join('products as P', 'variations.product_id', '=', 'P.id')
            ->leftJoin('units as U', 'P.unit_id', '=', 'U.id')
            ->where('P.business_id', $businessId)
            ->select(
                'variations.id',
                DB::raw("CONCAT(P.name, ' (', variations.sub_sku, ')') as text"),
                'variations.sub_sku',
                'P.name as product_name',
                'P.id as product_id',
                'P.enable_stock',
                'P.type',
                'P.sku',
                'P.brand_id',
                'P.category_id',
                'P.unit_id',
                'U.short_name as unit_name'
            )
            ->orderBy('P.name', 'asc')
            ->limit(20);

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('P.name', 'like', '%'.$term.'%')
                    ->orWhere('P.sku', 'like', '%'.$term.'%')
                    ->orWhere('variations.sub_sku', 'like', '%'.$term.'%');
            });
        }

        $permittedLocations = auth()->user()->permitted_locations();
        if ($permittedLocations != 'all') {
            $query->join('variation_location_details as VLD', 'variations.id', '=', 'VLD.variation_id')
                ->whereIn('VLD.location_id', $permittedLocations)
                ->groupBy('variations.id', 'P.id', 'P.name', 'variations.sub_sku', 'P.sku', 'P.brand_id', 'P.category_id', 'P.unit_id', 'U.short_name');
        }

        $results = $query->get();

        return [
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                    'product_id' => $item->product_id,
                ];
            }),
        ];
    }

    public function getVariationDetails($id)
    {
        if (!auth()->user()->can('damage_record.create') && !auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $variation = Variation::with(['product.brand', 'product.category', 'product.unit'])
            ->whereHas('product', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->findOrFail($id);

        $product = $variation->product;

        return [
            'variation_id' => $variation->id,
            'product_id' => $product->id,
            'product_name' => $product->name.' ('.$variation->sub_sku.')',
            'default_purchase_price' => $variation->default_purchase_price,
            'default_sell_price' => $variation->default_sell_price,
            'brand_name' => optional($product->brand)->name,
            'category_name' => optional($product->category)->name,
            'unit_name' => optional($product->unit)->short_name,
            'quantity_precision' => $product->unit ? ($product->unit->allow_decimal ? 2 : 0) : 2,
        ];
    }

    protected function formatContactForDisplay(?Contact $contact): ?string
    {
        if (!$contact) {
            return null;
        }

        $name = $contact->supplier_business_name ?: $contact->name ?: '';
        $reference = $contact->contact_id ? '#'.$contact->contact_id : '';
        $mobile = $contact->mobile ?: '';

        return trim(implode(' | ', array_filter([$name, $reference, $mobile])));
    }
    
    public function updateStatus($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);
            
            $newStatus = request()->input('status');
            
            if (!in_array($newStatus, ['not_dispatched', 'partial', 'dispatched'])) {
                throw new \Exception('Invalid status');
            }
            
            // Handle status changes properly based on dispatched quantity
            if ($newStatus === 'not_dispatched') {
                // Reset dispatched quantity and status
                $record->dispatched_quantity = 0;
                $record->dispatch_status = 'not_dispatched';
            } elseif ($newStatus === 'dispatched' || $newStatus === 'full_dispatch') {
                // If changing to fully dispatched, set dispatched quantity to full quantity
                $record->dispatched_quantity = $record->quantity;
                $record->dispatch_status = $newStatus;
            } else {
                // For "partial" status, don't change dispatched_quantity if it already exists
                // Only update it if it's being set from not_dispatched
                if ($record->dispatch_status == 'not_dispatched') {
                    // If marking as partial without a quantity, don't allow it
                    if ($record->dispatched_quantity == 0) {
                        throw new \Exception('Cannot set partial dispatch without quantity. Use the partial dispatch option from the menu.');
                    }
                }
                $record->dispatch_status = 'partial';
            }
            
            $record->save();

            return [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }
    
    public function partialDispatch($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            
            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);
            
            $dispatchQuantity = request()->input('dispatch_quantity');
            
            if (!$dispatchQuantity || $dispatchQuantity <= 0) {
                throw new \Exception(__('damage.invalid_dispatch_quantity'));
            }
            
            $remainingQuantity = $record->quantity - ($record->dispatched_quantity ?? 0);
            
            if ($dispatchQuantity > $remainingQuantity) {
                throw new \Exception(__('damage.dispatch_quantity_exceeds_remaining'));
            }
            
            // Update dispatched quantity
            $record->dispatched_quantity = ($record->dispatched_quantity ?? 0) + $dispatchQuantity;
            
            // Update dispatch status
            if ($record->dispatched_quantity >= $record->quantity) {
                $record->dispatch_status = 'dispatched';
            } else {
                $record->dispatch_status = 'partial';
            }
            
            $record->save();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('damage.partial_dispatch_success')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partial dispatch error', [
                'id' => $id,
                'dispatch_quantity' => request()->input('dispatch_quantity'),
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    public function approveRecord($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            
            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);
            
            // Only adjust stock if status is changing from not approved to approved
            if ($record->approval_status != 'approved' && $record->location_id) {
                // Get the quantity to deduct (use dispatched quantity if available, otherwise full quantity)
                $quantityToDeduct = ($record->dispatched_quantity > 0) ? $record->dispatched_quantity : $record->quantity;
                
                // Decrease product quantity for damage
                $this->productUtil->decreaseProductQuantity(
                    $record->product_id,
                    $record->variation_id,
                    $record->location_id,
                    $quantityToDeduct
                );
                
                // Create a stock adjustment transaction for stock history
                $refNo = $record->reference_no ? 'DMG-' . $record->reference_no : null;
                
                // Calculate purchase value based on dispatched quantity
                $purchaseValue = ($record->quantity > 0 && $quantityToDeduct > 0) 
                    ? ($record->purchase_value / $record->quantity) * $quantityToDeduct 
                    : 0;
                
                $transaction = \App\Transaction::create([
                    'business_id' => $businessId,
                    'location_id' => $record->location_id,
                    'type' => 'stock_adjustment',
                    'status' => 'final',
                    'transaction_date' => now(),
                    'ref_no' => $refNo,
                    'total_before_tax' => $purchaseValue,
                    'final_total' => $purchaseValue,
                    'created_by' => auth()->user()->id,
                    'adjustment_type' => 'abnormal',
                    'additional_notes' => __('damage.damage_record') . ': ' . ($record->reference_no ?? 'N/A') . ' - ' . __('damage.record_approved'),
                ]);
                
                // Calculate unit price based on dispatched quantity
                $unitPrice = ($record->quantity > 0 && $quantityToDeduct > 0) 
                    ? ($record->unit_purchase_price ?? 0)
                    : ($record->unit_purchase_price ?? 0);
                
                \App\StockAdjustmentLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $record->product_id,
                    'variation_id' => $record->variation_id,
                    'quantity' => $quantityToDeduct,
                    'unit_price' => $unitPrice,
                ]);
                
                // Set dispatch status and dispatched quantity if not already dispatched
                // When approving without partial dispatch, mark as fully dispatched
                if ($record->dispatch_status == 'not_dispatched') {
                    $record->dispatched_quantity = $record->quantity;
                    $record->dispatch_status = 'full_dispatch';
                }
            }
            
            // Always set dispatch status and dispatched quantity when approving (even if no stock adjustment)
            if ($record->approval_status != 'approved' && $record->dispatch_status == 'not_dispatched') {
                $record->dispatched_quantity = $record->quantity;
                $record->dispatch_status = 'full_dispatch';
            }
            
            $record->approval_status = 'approved';
            $record->approved_by = auth()->user()->id;
            $record->approved_at = now();
            $record->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('damage.record_approved')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage approve error', [
                'id' => $id,
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    public function rejectRecord($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            
            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);
            
            // If was approved before, return stock and delete transaction
            if ($record->approval_status == 'approved' && $record->location_id) {
                // Delete the original transaction
                $refNo = $record->reference_no ? 'DMG-' . $record->reference_no : null;
                if ($refNo) {
                    // Find and delete the transaction by reference number
                    $transaction = \App\Transaction::where('business_id', $businessId)
                        ->where('ref_no', $refNo)
                        ->where('type', 'stock_adjustment')
                        ->first();
                    
                    if ($transaction) {
                        // Delete the stock adjustment lines first
                        \App\StockAdjustmentLine::where('transaction_id', $transaction->id)->delete();
                        // Delete the transaction
                        \App\Transaction::where('id', $transaction->id)->delete();
                    }
                }
                
                // Get the quantity that was approved (dispatched quantity or full quantity)
                $quantityToReturn = ($record->dispatched_quantity > 0) ? $record->dispatched_quantity : $record->quantity;
                
                // Increase product quantity back
                $this->productUtil->updateProductQuantity(
                    $record->location_id,
                    $record->product_id,
                    $record->variation_id,
                    $quantityToReturn,
                    0,
                    null,
                    false
                );
            }
            
            $record->approval_status = 'rejected';
            $record->approved_by = auth()->user()->id;
            $record->approved_at = now();
            $record->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('damage.record_rejected')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage reject error', [
                'id' => $id,
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    public function updateApprovalStatus($id)
    {
        if (!auth()->user()->can('damage_record.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            
            $businessId = request()->session()->get('user.business_id');
            $record = DamageRecord::forBusiness($businessId)->findOrFail($id);
            
            $newStatus = request()->input('status');
            $oldStatus = $record->approval_status;
            
            if (!in_array($newStatus, ['pending', 'approved', 'rejected'])) {
                throw new \Exception('Invalid status');
            }
            
            // Handle stock adjustments
            if ($oldStatus != 'approved' && $newStatus == 'approved' && $record->location_id) {
                // Get the quantity to deduct (use dispatched quantity if available, otherwise full quantity)
                $quantityToDeduct = ($record->dispatched_quantity > 0) ? $record->dispatched_quantity : $record->quantity;
                
                // Approving - decrease stock
                $this->productUtil->decreaseProductQuantity(
                    $record->product_id,
                    $record->variation_id,
                    $record->location_id,
                    $quantityToDeduct
                );
                
                // Create stock adjustment transaction
                $refNo = $record->reference_no ? 'DMG-' . $record->reference_no : null;
                
                // Calculate purchase value based on dispatched quantity
                $purchaseValue = ($record->quantity > 0 && $quantityToDeduct > 0) 
                    ? ($record->purchase_value / $record->quantity) * $quantityToDeduct 
                    : 0;
                
                $transaction = \App\Transaction::create([
                    'business_id' => $businessId,
                    'location_id' => $record->location_id,
                    'type' => 'stock_adjustment',
                    'status' => 'final',
                    'transaction_date' => now(),
                    'ref_no' => $refNo,
                    'total_before_tax' => $purchaseValue,
                    'final_total' => $purchaseValue,
                    'created_by' => auth()->user()->id,
                    'adjustment_type' => 'abnormal',
                    'additional_notes' => __('damage.damage_record') . ': ' . ($record->reference_no ?? 'N/A'),
                ]);
                
                // Calculate unit price based on dispatched quantity
                $unitPrice = ($record->quantity > 0 && $quantityToDeduct > 0) 
                    ? ($record->unit_purchase_price ?? 0)
                    : ($record->unit_purchase_price ?? 0);
                
                \App\StockAdjustmentLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $record->product_id,
                    'variation_id' => $record->variation_id,
                    'quantity' => $quantityToDeduct,
                    'unit_price' => $unitPrice,
                ]);
                
                // Set dispatch status and dispatched quantity if not already dispatched
                if ($record->dispatch_status == 'not_dispatched') {
                    $record->dispatched_quantity = $record->quantity;
                    $record->dispatch_status = 'full_dispatch';
                }
                
            }
            
            // Always set dispatch status and dispatched quantity when approving (even if no stock adjustment)
            if ($newStatus == 'approved' && $record->dispatch_status == 'not_dispatched') {
                $record->dispatched_quantity = $record->quantity;
                $record->dispatch_status = 'full_dispatch';
            } elseif ($oldStatus == 'approved' && $newStatus != 'approved' && $record->location_id) {
                // Rejecting or pending after approval - delete the original transaction
                $refNo = $record->reference_no ? 'DMG-' . $record->reference_no : null;
                if ($refNo) {
                    // Find and delete the transaction by reference number
                    $transaction = \App\Transaction::where('business_id', $businessId)
                        ->where('ref_no', $refNo)
                        ->where('type', 'stock_adjustment')
                        ->first();
                    
                    if ($transaction) {
                        // Delete the stock adjustment lines first
                        \App\StockAdjustmentLine::where('transaction_id', $transaction->id)->delete();
                        // Delete the transaction
                        \App\Transaction::where('id', $transaction->id)->delete();
                    }
                }
                
                // Get the quantity that was approved (dispatched quantity or full quantity)
                $quantityToReturn = ($record->dispatched_quantity > 0) ? $record->dispatched_quantity : $record->quantity;
                
                // Increase product quantity back
                $this->productUtil->updateProductQuantity(
                    $record->location_id,
                    $record->product_id,
                    $record->variation_id,
                    $quantityToReturn,
                    0,
                    null,
                    false
                );
                
            }
            
            $record->approval_status = $newStatus;
            
            // Set approved_by and approved_at if approving or rejecting
            if ($newStatus != 'pending') {
                $record->approved_by = auth()->user()->id;
                $record->approved_at = now();
            } else {
                $record->approved_by = null;
                $record->approved_at = null;
            }
            
            $record->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.success')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Damage approval status update error', [
                'id' => $id,
                'new_status' => request()->input('status'),
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
}

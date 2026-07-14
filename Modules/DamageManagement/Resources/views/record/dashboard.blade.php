@extends('layouts.app')
@section('title', 'Damage Management Dashboard')

@section('content')
<section class="content-header no-print">
    <h1><i class="fa fa-dashboard"></i> Damage Management Dashboard</h1>
</section>

<style>
.modern-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    color: white;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.modern-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transition: all 0.5s ease;
}
.modern-card:hover::before {
    top: -20%;
    right: -20%;
}
.modern-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}
.modern-card-aqua { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.modern-card-yellow { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.modern-card-green { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.modern-card-red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.modern-card-maroon { background: linear-gradient(135deg, #eb4786 0%, #ffe57f 100%); }
.modern-card-purple { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
.modern-card-olive { background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); }
.modern-card-teal { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333; }
.modern-card-navy { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
.modern-card-fuchsia { background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%); color: #333; }
.modern-card-orange { background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%); }
.modern-card-lime { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #333; }

.modern-card-icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 70px;
    opacity: 0.3;
    transition: all 0.3s ease;
}
.modern-card:hover .modern-card-icon {
    transform: scale(1.2) rotate(5deg);
    opacity: 0.5;
}
.modern-card-value {
    font-size: 36px;
    font-weight: bold;
    margin: 10px 0;
}
.modern-card-label {
    font-size: 14px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.modern-card-link {
    display: block;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(255,255,255,0.3);
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}
.modern-card-link:hover {
    color: white;
    text-decoration: none;
    padding-left: 10px;
}
.modern-card-link:hover i {
    transform: translateX(5px);
}
</style>

<section class="content no-print">
    <!-- Statistics Cards Row 1 -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-aqua">
                <div class="modern-card-icon">
                    <i class="fa fa-list"></i>
                </div>
                <div class="modern-card-value">{{ $totalRecords }}</div>
                <div class="modern-card-label">Total Damage Records</div>
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index']) }}" class="modern-card-link">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-yellow">
                <div class="modern-card-icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="modern-card-value">{{ $pendingRecords }}</div>
                <div class="modern-card-label">Pending Records</div>
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index']) }}" class="modern-card-link">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-green">
                <div class="modern-card-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="modern-card-value">{{ $approvedRecords }}</div>
                <div class="modern-card-label">Approved Records</div>
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index']) }}" class="modern-card-link">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-red">
                <div class="modern-card-icon">
                    <i class="fa fa-times-circle"></i>
                </div>
                <div class="modern-card-value">{{ $rejectedRecords }}</div>
                <div class="modern-card-label">Rejected Records</div>
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index']) }}" class="modern-card-link">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Financial Statistics Row 2 -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-maroon">
                <div class="modern-card-icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="modern-card-value"><span class="display_currency" data-currency_symbol="true">{{ $totalPurchaseValue }}</span></div>
                <div class="modern-card-label">Total Purchase Value</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-purple">
                <div class="modern-card-icon">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <div class="modern-card-value"><span class="display_currency" data-currency_symbol="true">{{ $totalSellValue }}</span></div>
                <div class="modern-card-label">Total Sell Value</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-olive">
                <div class="modern-card-icon">
                    <i class="fa fa-money-check"></i>
                </div>
                <div class="modern-card-value"><span class="display_currency" data-currency_symbol="true">{{ $totalExpectedCompensation }}</span></div>
                <div class="modern-card-label">Expected Compensation</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-teal">
                <div class="modern-card-icon">
                    <i class="fa fa-hand-holding-usd"></i>
                </div>
                <div class="modern-card-value"><span class="display_currency" data-currency_symbol="true">{{ $totalGivenCompensation }}</span></div>
                <div class="modern-card-label">Given Compensation</div>
            </div>
        </div>
    </div>

    <!-- Quantity Statistics Row 3 -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-navy">
                <div class="modern-card-icon">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="modern-card-value">{{ @format_quantity($totalQuantity) }}</div>
                <div class="modern-card-label">Total Quantity</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-fuchsia">
                <div class="modern-card-icon">
                    <i class="fa fa-truck"></i>
                </div>
                <div class="modern-card-value">{{ @format_quantity($totalDispatchedQuantity) }}</div>
                <div class="modern-card-label">Total Dispatched</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-orange">
                <div class="modern-card-icon">
                    <i class="fa fa-box"></i>
                </div>
                <div class="modern-card-value">{{ $uniqueProducts }}</div>
                <div class="modern-card-label">Products Affected</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="modern-card modern-card-lime">
                <div class="modern-card-icon">
                    <i class="fa fa-minus-circle"></i>
                </div>
                <div class="modern-card-value">{{ @format_quantity($totalQuantity - $totalDispatchedQuantity) }}</div>
                <div class="modern-card-label">Remaining Quantity</div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Damage Records by Month</h3>
                </div>
                <div class="box-body">
                    <canvas id="monthlyChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Dispatch Status Distribution</h3>
                </div>
                <div class="box-body">
                    <canvas id="dispatchStatusChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Approval Status Distribution</h3>
                </div>
                <div class="box-body">
                    <canvas id="approvalStatusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Financial Summary</h3>
                </div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr>
                            <td><strong>Total Purchase Value:</strong></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $totalPurchaseValue }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Sell Value:</strong></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $totalSellValue }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Expected Compensation:</strong></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $totalExpectedCompensation }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Given Compensation:</strong></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $totalGivenCompensation }}</span></td>
                        </tr>
                        <tr class="info">
                            <td><strong>Difference:</strong></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $totalExpectedCompensation - $totalGivenCompensation }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Breakdown and Recent Records Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Damage Records by Location</h3>
                </div>
                <div class="box-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th class="text-right">Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locationData as $location)
                                <tr>
                                    <td>{{ $location->location_name ?: 'N/A' }}</td>
                                    <td class="text-right">{{ $location->count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Damage Records</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Product</th>
                                <th>Location</th>
                                <th class="text-right">Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRecords as $record)
                                <tr>
                                    <td><a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'show'], [$record->id]) }}">{{ $record->reference_no }}</a></td>
                                    <td>{{ $record->product_name }}</td>
                                    <td>{{ $record->location_name ?: 'N/A' }}</td>
                                    <td class="text-right">{{ @format_quantity($record->quantity) }}</td>
                                    <td>
                                        @if($record->approval_status == 'approved')
                                            <span class="label label-success">{{ $record->approval_status }}</span>
                                        @elseif($record->approval_status == 'rejected')
                                            <span class="label label-danger">{{ $record->approval_status }}</span>
                                        @else
                                            <span class="label label-warning">{{ $record->approval_status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No recent records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Dispatched Products -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Top 10 Dispatched Products</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Brand</th>
                                <th class="text-right">Total Dispatched Qty</th>
                                <th class="text-right">Purchase Value</th>
                                <th class="text-right">Sell Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDispatchedProducts as $index => $product)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $product->product_name }}</td>
                                    <td>{{ $product->sub_sku ?? '-' }}</td>
                                    <td>{{ $product->brand_name ?? '-' }}</td>
                                    <td class="text-right">{{ @format_quantity($product->total_dispatched_quantity) }}</td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $product->total_purchase_value }}</span></td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $product->total_sell_value }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No dispatched products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Monthly Chart
    var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    var monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyData->pluck('month')) !!},
            datasets: [{
                label: 'Damage Records',
                data: {!! json_encode($monthlyData->pluck('count')) !!},
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });

    // Dispatch Status Chart
    var dispatchCtx = document.getElementById('dispatchStatusChart');
    if (dispatchCtx) {
        dispatchCtx = dispatchCtx.getContext('2d');
        var dispatchChart = new Chart(dispatchCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($dispatchStatusData->pluck('dispatch_status')) !!},
                datasets: [{
                    data: {!! json_encode($dispatchStatusData->pluck('count')) !!},
                    backgroundColor: [
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });
    }

    // Approval Status Chart
    var approvalCtx = document.getElementById('approvalStatusChart');
    if (approvalCtx) {
        approvalCtx = approvalCtx.getContext('2d');
        var approvalChart = new Chart(approvalCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($approvalStatusData->pluck('approval_status')) !!},
                datasets: [{
                    data: {!! json_encode($approvalStatusData->pluck('count')) !!},
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });
    }
    
    // Initialize currency display
    __currency_convert_recursively($('.content'));
});
</script>
@endsection


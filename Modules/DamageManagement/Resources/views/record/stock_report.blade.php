@extends('layouts.app')
@section('title', 'Damage Stock Report')

@section('content')
<section class="content-header no-print">
    <h1><i class="fa fa-file-text"></i> Damage Stock Report</h1>
</section>

<section class="content no-print">
    <!-- Filters -->
    @component('components.widget', ['class' => 'box-primary'])
        @slot('title')
            <i class="fa fa-filter"></i> @lang('report.filters')
        @endslot
        <form method="GET" action="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'stockReport']) }}" id="stock_report_filter_form">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('stock_report_filter_location', __('business.location') . ':') !!}
                    {!! Form::select('location_id', $locations, $location_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('stock_report_filter_brand', __('product.brand') . ':') !!}
                    {!! Form::select('brand_id', $brands, $brand_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('stock_report_filter_category', __('product.category') . ':') !!}
                    {!! Form::select('category_id', $categories, $category_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('stock_report_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', $date_range ?? null, ['class' => 'form-control', 'id' => 'stock_report_filter_date_range', 'readonly', 'placeholder' => __('lang_v1.select_a_date_range')]) !!}
                </div>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> @lang('messages.filter')</button>
                <button type="button" class="btn btn-default" onclick="window.location.href='{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'stockReport']) }}'"><i class="fa fa-refresh"></i> @lang('messages.reset')</button>
            </div>
        </form>
    @endcomponent

    <!-- Report -->
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-info" onclick="exportToCopy();"><i class="fa fa-copy"></i> @lang('damagemanagement::damage.copy')</button>
                <button type="button" class="btn btn-default" onclick="exportToCsv();"><i class="fa fa-file-text-o"></i> @lang('damagemanagement::damage.csv')</button>
                <button type="button" class="btn btn-success" onclick="exportToExcel();"><i class="fa fa-file-excel-o"></i> @lang('damagemanagement::damage.excel')</button>
                <button type="button" class="btn btn-danger" onclick="exportToPdf();"><i class="fa fa-file-pdf-o"></i> @lang('damagemanagement::damage.pdf')</button>
                <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i> @lang('messages.print')</button>
            </div>
        @endslot

        <style>
        .excel-style-table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
        }
        .excel-style-table th,
        .excel-style-table td {
            border: 1px solid #000;
            padding: 8px 12px;
            text-align: left;
            background-color: #fff;
        }
        .excel-style-table th {
            background-color: #4472C4 !important;
            color: #fff !important;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }
        .excel-style-table tbody tr:nth-child(even) {
            background-color: #F2F2F2;
        }
        .excel-style-table tbody tr:hover {
            background-color: #DCE6F1;
        }
        .excel-style-table tfoot tr {
            background-color: #E7E6E6 !important;
            font-weight: bold;
        }
        .excel-style-table .text-right {
            text-align: right;
        }
        .excel-style-table .text-center {
            text-align: center;
        }
        
        /* Print styles */
        @media print {
            @page {
                margin: 1cm;
            }
            body * {
                visibility: hidden;
            }
            .print-container, .print-container * {
                visibility: visible;
            }
            .print-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .excel-style-table {
                font-size: 10pt;
            }
            .excel-style-table th {
                background-color: #4472C4 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .excel-style-table tbody tr:nth-child(even) {
                background-color: #F2F2F2;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        </style>
        <div class="table-responsive">
            <div class="print-container">
                <table class="excel-style-table" id="stock_report_table">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center" style="width: 50px;">#</th>
                        <th rowspan="2" style="width: 200px;">@lang('product.product')</th>
                        <th rowspan="2" style="width: 100px;">SKU</th>
                        <th rowspan="2" style="width: 120px;">@lang('product.brand')</th>
                        <th rowspan="2" style="width: 120px;">@lang('business.location')</th>
                        <th colspan="3" class="text-center" style="background-color: #70AD47 !important; color: #fff !important;">Stock Information</th>
                    </tr>
                    <tr>
                        <th class="text-right" style="background-color: #4472C4 !important; color: #fff !important;">Current Stock</th>
                        <th class="text-right" style="background-color: #4472C4 !important; color: #fff !important;">Dispatched Qty</th>
                        <th class="text-right" style="background-color: #4472C4 !important; color: #fff !important;">Closing Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $record)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $record->product_name }}</td>
                            <td>{{ $record->sub_sku ?: '-' }}</td>
                            <td>{{ $record->brand_name ?: '-' }}</td>
                            <td>{{ $record->location_name ?: '-' }}</td>
                            <td class="text-right">{{ @format_quantity($record->current_stock) }}</td>
                            <td class="text-right">{{ @format_quantity($record->total_dispatched_quantity) }}</td>
                            <td class="text-right">{{ @format_quantity($record->closing_stock) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">@lang('messages.no_data_found')</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>{{ @format_quantity($records->sum('current_stock')) }}</strong></td>
                        <td class="text-right"><strong>{{ @format_quantity($records->sum('total_dispatched_quantity')) }}</strong></td>
                        <td class="text-right"><strong>{{ @format_quantity($records->sum('closing_stock')) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
function exportToCopy() {
    var table = document.getElementById('stock_report_table');
    var range = document.createRange();
    range.selectNode(table);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    
    try {
        document.execCommand('copy');
        toastr.success('Table copied to clipboard');
    } catch (err) {
        toastr.error('Failed to copy');
    }
    
    window.getSelection().removeAllRanges();
}

function exportToCsv() {
    var table = document.getElementById('stock_report_table');
    var rows = table.querySelectorAll('tr');
    var csv = [];
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            var data = cols[j].innerText.replace(/"|'/g, '');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    var downloadLink = document.createElement('a');
    downloadLink.download = 'Damage_Stock_Report.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportToExcel() {
    var table = document.getElementById('stock_report_table');
    var html = table.outerHTML;
    var url = 'data:application/vnd.ms-excel,' + escape(html);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'Damage_Stock_Report.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToPdf() {
    window.print();
}

$(document).ready(function() {
    // Initialize Select2
    if ($('.select2').length > 0) {
        $('.select2').select2();
    }

    // Date range picker
    $('#stock_report_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
        $('#stock_report_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        $('#stock_report_filter_date_range').data('start_date', start.format('YYYY-MM-DD'));
        $('#stock_report_filter_date_range').data('end_date', end.format('YYYY-MM-DD'));
    }).on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $(this).data('start_date', '');
        $(this).data('end_date', '');
    });

    // Initialize DataTable with Excel-like appearance
    $('#stock_report_table').DataTable({
        "paging": true,
        "lengthChange": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "order": [[1, 'asc']],
        "pageLength": 25,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search products..."
        },
        "dom": '<"top"lf<"clear">>rt<"bottom"ip<"clear">>',
        "buttons": [],
        "pagingType": "simple_numbers",
        "scrollX": true,
        "stripeClasses": ['', 'excel-alt-row']
    });
    
    // Add Excel-style footer styling
    $('.dataTables_wrapper').css({
        'font-family': 'Arial, sans-serif',
        'font-size': '12px'
    });
});
</script>
@endsection

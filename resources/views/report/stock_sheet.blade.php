@extends('layouts.app')
@section('title', __('report.stock_sheet'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_sheet') }}</h1>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => '#', 'method' => 'get', 'id' => 'stock_sheet_filter_form' ]) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('stock_sheet_location_id',  __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'stock_sheet_location_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('stock_sheet_category_id', __('category.category') . ':') !!}
                            {!! Form::select('category_id', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'stock_sheet_category_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('stock_sheet_brand_id', __('product.brand') . ':') !!}
                            {!! Form::select('brand_id', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'stock_sheet_brand_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('stock_sheet_unit_id',__('product.unit') . ':') !!}
                            {!! Form::select('unit_id', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'stock_sheet_unit_id']); !!}
                        </div>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="stock_sheet_table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>@lang('business.product')</th>
                                <th>@lang('lang_v1.variation')</th>
                                <th>@lang('product.category')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('lang_v1.physical_count')</th>
                                <th>@lang('lang_v1.difference')</th>
                                <th>@lang('lang_v1.note')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_stock_sheet_total"></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var stock_sheet_table = $('#stock_sheet_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            order: [[1, 'asc']],
            scrollY: '75vh',
            scrollX: true,
            scrollCollapse: true,
            ajax: {
                url: '{{ action([\App\Http\Controllers\ReportController::class, 'getStockSheet']) }}',
                data: function (d) {
                    d.location_id = $('#stock_sheet_location_id').val();
                    d.category_id = $('#stock_sheet_category_id').val();
                    d.brand_id = $('#stock_sheet_brand_id').val();
                    d.unit_id = $('#stock_sheet_unit_id').val();
                },
            },
            columns: [
                { data: 'sku', name: 'variations.sub_sku' },
                { data: 'product', name: 'p.name' },
                { data: 'variation', name: 'variation' },
                { data: 'category_name', name: 'c.name' },
                { data: 'location_name', name: 'l.name' },
                { data: 'stock', name: 'stock', searchable: false },
                { data: 'physical_count', name: 'physical_count', orderable: false, searchable: false },
                { data: 'difference', name: 'difference', orderable: false, searchable: false },
                { data: 'count_note', name: 'count_note', orderable: false, searchable: false },
            ],
            footerCallback: function (row, data) {
                var total_stock = 0;
                for (var r in data) {
                    total_stock += $(data[r].stock).data('orig-value') ? parseFloat($(data[r].stock).data('orig-value')) : 0;
                }
                $('.footer_stock_sheet_total').html(__currency_trans_from_en(total_stock, false));
            },
        });

        $('#stock_sheet_location_id, #stock_sheet_category_id, #stock_sheet_brand_id, #stock_sheet_unit_id').change(function () {
            stock_sheet_table.ajax.reload();
        });
    });
</script>
@endsection

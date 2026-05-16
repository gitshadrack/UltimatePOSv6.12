@component('components.filters', ['title' => __('report.filters')])
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label($date_range_id, __('report.date_range') . ':') !!}
            {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => $date_range_id, 'readonly']); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label($location_id, __('purchase.business_location') . ':') !!}
            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => $location_id, 'placeholder' => __('messages.all')]); !!}
        </div>
    </div>
    @if(!empty($status_id))
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label($status_id, __('lang_v1.etims_status') . ':') !!}
                {!! Form::select('etims_status', [
                    'pending' => __('lang_v1.etims_pending'),
                    'submitted' => __('lang_v1.etims_submitted'),
                    'accepted' => __('lang_v1.etims_accepted'),
                    'failed' => __('lang_v1.etims_failed'),
                    'cancelled' => __('lang_v1.etims_cancelled')
                ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => $status_id, 'placeholder' => __('messages.all')]); !!}
            </div>
        </div>
    @endif
@endcomponent

@extends('layouts.app')

@section('title', __('damagemanagement::damage.create_dispatch'))

@section('content')
<section class="content-header">
    <h1>@lang('damagemanagement::damage.create_dispatch')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\Modules\DamageManagement\Http\Controllers\DamageDispatchController::class, 'store']), 
                    'method' => 'post', 'id' => 'damage_dispatch_form']) !!}
    
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('dispatched_at', __('lang_v1.date') . ':*') !!}
                            {!! Form::text('dispatched_at', now()->format('Y-m-d H:i:s'), ['class' => 'form-control', 
                                'required', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('business.business_location') . ':') !!}
                            {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('reference_no', __('lang_v1.reference_no') . ':') !!}
                            {!! Form::text('reference_no', null, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success'])
                <h4>@lang('damagemanagement::damage.available_records')</h4>
                <div id="available_records_container"></div>
            @endcomponent
        </div>
    </div>

    <div class="row" id="selected_records_container" style="display: none;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-warning'])
                <h4>@lang('damagemanagement::damage.dispatch_lines')</h4>
                <div id="selected_records_table"></div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <a href="{{action([\Modules\DamageManagement\Http\Controllers\DamageDispatchController::class, 'index'])}}" 
               class="btn btn-default">@lang('messages.cancel')</a>
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize available records table
        loadAvailableRecords();

        function loadAvailableRecords() {
            var location_id = $('select[name="location_id"]').val();
            $('#available_records_container').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
            
            $.ajax({
                url: '{{action([\Modules\DamageManagement\Http\Controllers\DamageDispatchController::class, "availableRecords"])}}',
                data: {location_id: location_id},
                dataType: 'html',
                success: function(data) {
                    $('#available_records_container').html(data);
                }
            });
        }

        $('select[name="location_id"]').on('change', function() {
            loadAvailableRecords();
        });

        // Handle adding damage records to dispatch
        $(document).on('click', '.add-damage-record', function() {
            // Add to selected records
        });

        // Handle form submission
        $('#damage_dispatch_form').on('submit', function(e) {
            e.preventDefault();
            // Submit form
        });
    });
</script>
@endsection


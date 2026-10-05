<div class="modal fade" tabindex="-1" role="dialog" id="modal_payment">
    @php
        $unified_payment_enabled = !array_key_exists('enable_unified_payment_modal', $pos_settings ?? [])
            || !empty($pos_settings['enable_unified_payment_modal']);
    @endphp
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-money-check-alt"></i> {{ $unified_payment_enabled ? __('lang_v1.complete_payment') : __('lang_v1.payment') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-12">
                        <strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
                        {!! Form::hidden('advance_balance', null, [
                            'id' => 'advance_balance',
                            'data-error-msg' => __('lang_v1.required_advance_balance_not_available'),
                        ]) !!}
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div id="payment_rows_div" class="row unified-payment-grid">
                                @if ($unified_payment_enabled)
                                @php
                                    $pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
                                    $show_in_pos = '';


                                    if (isset($pos_settings['enable_cash_denomination_on']) && ($pos_settings['enable_cash_denomination_on'] == 'all_screens' || $pos_settings['enable_cash_denomination_on'] == 'pos_screen')) {
                                        $show_in_pos = true;
                                    }
                                    $is_intasend_enabled = $is_intasend_enabled ?? (!empty($enabled_modules) && in_array('intasend', $enabled_modules));
                                    $is_daraja_enabled = $is_daraja_enabled ?? (!empty($enabled_modules) && in_array('daraja', $enabled_modules));
                                    
                                @endphp
                                @php
                                    $available_lines = collect($payment_lines)->filter(function ($line) use (&$change_return) {
                                        if (!empty($line['is_return'])) {
                                            $change_return = $line;
                                            return false;
                                        }
                                        return true;
                                    })->values();
                                    $ordered_methods = collect($payment_types)->keys()->sortBy(function ($method) {
                                        return $method === 'cash' ? 0 : ($method === 'custom_pay_1' ? 1 : ($method === 'card' ? 2 : ($method === 'advance' ? 99 : 10)));
                                    })->values();
                                    $used_lines = [];
                                    $unified_rows = [];
                                    foreach ($ordered_methods as $method) {
                                        $match_index = $available_lines->search(function ($line, $index) use ($method, $used_lines) {
                                            return !in_array($index, $used_lines, true) && ($line['method'] ?? null) === $method;
                                        });
                                        if ($match_index !== false) {
                                            $line = $available_lines[$match_index];
                                            $used_lines[] = $match_index;
                                        } else {
                                            $line = [
                                                'amount' => 0,
                                                'method' => $method,
                                                'cash_tendered' => 0,
                                                'transaction_no' => '',
                                                'card_transaction_number' => '',
                                                'cheque_number' => '',
                                                'bank_account_number' => '',
                                                'account_id' => '',
                                                'note' => '',
                                                'is_return' => 0,
                                            ];
                                        }
                                        $line['method'] = $method;
                                        $unified_rows[] = ['line' => $line, 'label' => $payment_types[$method]];
                                    }
                                    foreach ($available_lines as $index => $line) {
                                        if (!in_array($index, $used_lines, true)) {
                                            $unified_rows[] = ['line' => $line, 'label' => $payment_types[$line['method']] ?? $line['method']];
                                        }
                                    }
                                @endphp
                                @foreach ($unified_rows as $unified_row)
                                    @include('sale_pos.partials.unified_payment_row', [
                                        'row_index' => $loop->index,
                                        'payment_line' => $unified_row['line'],
                                        'payment_label' => $unified_row['label'],
                                    ])
                                @endforeach
                                @else
                                    @foreach ($payment_lines as $payment_line)
                                        @if (!empty($payment_line['is_return']))
                                            @php $change_return = $payment_line; @endphp
                                            @continue
                                        @endif
                                        @include('sale_pos.partials.payment_row', [
                                            'removable' => !$loop->first,
                                            'row_index' => $loop->index,
                                            'payment_line' => $payment_line,
                                            'show_denomination' => true,
                                            'show_in_pos' => $show_in_pos,
                                        ])
                                    @endforeach
                                    @php $unified_rows = $payment_lines; @endphp
                                @endif
                            </div>
                            <input type="hidden" id="payment_row_index" value="{{ count($unified_rows) }}">
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-default btn-sm" id="add-payment-row">
                                    <i class="fa fa-plus"></i> @lang('sale.add_payment_row')
                                </button>
                            </div>
                        </div>
                        <br>
                        <div class="row @if ($change_return['amount'] == 0) hide @endif payment_row"
                            id="change_return_payment_data">
                            <div class="col-md-12">
                                <div class="box box-solid payment_row bg-lightgray">
                                    <div class="box-body">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('change_return_method', __('lang_v1.change_return_payment_method') . ':*') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fas fa-money-bill-alt"></i>
                                                    </span>
                                                    @php
                                                        $_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];

                                                        $_payment_types = $payment_types;
                                                        if (isset($_payment_types['advance'])) {
                                                            unset($_payment_types['advance']);
                                                        }
                                                    @endphp
                                                    {!! Form::select('payment[change_return][method]', $_payment_types, $_payment_method, [
                                                        'class' => 'form-control col-md-12 payment_types_dropdown',
                                                        'id' => 'change_return_method',
                                                        'style' => 'width:100%;',
                                                    ]) !!}
                                                </div>
                                            </div>
                                        </div>
                                        @if (!empty($accounts))
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    {!! Form::label('change_return_account', __('lang_v1.change_return_payment_account') . ':') !!}
                                                    <div class="input-group">
                                                        <span class="input-group-addon">
                                                            <i class="fas fa-money-bill-alt"></i>
                                                        </span>
                                                        {!! Form::select(
                                                            'payment[change_return][account_id]',
                                                            $accounts,
                                                            !empty($change_return['account_id']) ? $change_return['account_id'] : '',
                                                            ['class' => 'form-control select2', 'id' => 'change_return_account', 'style' => 'width:100%;'],
                                                        ) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="clearfix"></div>
                                        @include('sale_pos.partials.payment_type_details', [
                                            'payment_line' => $change_return,
                                            'row_index' => 'change_return',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if (!empty($pos_settings['enable_kra_customer_details']))
                        <div class="row">
                            <div class="col-md-12">
                                <div class="box box-solid box-info">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fas fa-id-card"></i> @lang('lang_v1.kra_customer_details')</h3>
                                    </div>
                                    <div class="box-body">
                                        <p class="help-block">@lang('lang_v1.kra_customer_details_help')</p>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {!! Form::label('kra_customer_name', __('lang_v1.kra_customer_name') . ':') !!}
                                                {!! Form::text('kra_customer_name', !empty($transaction) ? $transaction->kra_customer_name : null, [
                                                    'class' => 'form-control', 'id' => 'kra_customer_name', 'maxlength' => 191, 'autocomplete' => 'name',
                                                ]) !!}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {!! Form::label('kra_pin', __('lang_v1.kra_pin') . ':') !!}
                                                {!! Form::text('kra_pin', !empty($transaction) ? $transaction->kra_pin : null, [
                                                    'class' => 'form-control text-uppercase', 'id' => 'kra_pin', 'maxlength' => 11,
                                                    'placeholder' => 'A123456789B', 'pattern' => '[A-Za-z][0-9]{9}[A-Za-z]',
                                                    'title' => __('lang_v1.kra_pin_format_help'), 'autocomplete' => 'off',
                                                ]) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('sale_note', __('sale.sell_note') . ':') !!}
                                    {!! Form::textarea('sale_note', !empty($transaction) ? $transaction->additional_notes : null, [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => __('sale.sell_note'),
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('staff_note', __('sale.staff_note') . ':') !!}
                                    {!! Form::textarea('staff_note', !empty($transaction) ? $transaction->staff_note : null, [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => __('sale.staff_note'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid bg-orange">
                            <div class="box-body">
                                <div class="col-md-12">
                                    <strong>
                                        @lang('lang_v1.total_items'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_quantity">0</span>
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('sale.total_payable'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_payable_span">0</span>
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.total_paying'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_paying">0</span>
                                    <input type="hidden" id="total_paying_input">
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.change_return'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold change_return_span">0</span>
                                    {!! Form::hidden('change_return', $change_return['amount'], [
                                        'class' => 'form-control change_return input_number',
                                        'required',
                                        'id' => 'change_return',
                                    ]) !!}
                                    <!-- <span class="lead text-bold total_quantity">0</span> -->
                                    @if (!empty($change_return['id']))
                                        <input type="hidden" name="change_return_id"
                                            value="{{ $change_return['id'] }}">
                                    @endif
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.balance'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold balance_due">0</span>
                                    <input type="hidden" id="in_balance_due" value=0>
                                </div>

                                <div class="col-md-12 hide mpesa_excess_credit_option">
                                    <hr>
                                    <label>
                                        <input type="checkbox" class="store_mpesa_excess_as_advance_toggle">
                                        @lang('lang_v1.store_mpesa_excess_as_credit')
                                        (<span class="mpesa_excess_credit_amount"></span>)
                                    </label>
                                    <p class="help-block">@lang('lang_v1.store_mpesa_excess_as_credit_help')</p>
                                </div>
                                <input type="hidden" name="store_mpesa_excess_as_advance"
                                    id="store_mpesa_excess_as_advance" value="0">



                            </div>
                            <!-- /.box-body -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save"
                    data-default-text="{{ $unified_payment_enabled ? __('lang_v1.complete_sale') : __('sale.finalize_payment') }}" data-processing-text="@lang('lang_v1.processing')">
                    <i class="fas fa-check-circle"></i> <span>{{ $unified_payment_enabled ? __('lang_v1.complete_sale') : __('sale.finalize_payment') }}</span>
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<style>
    #modal_payment .modal-dialog { width: min(1180px, calc(100% - 20px)); }
    #modal_payment .modal-body { max-height: calc(100vh - 170px); overflow-y: auto; }
    #modal_payment .unified-payment-grid { margin: 0 -8px; }
    #modal_payment .unified-payment-column { padding: 0 8px; }
    #modal_payment .unified-payment-section { border-top: 3px solid #3c8dbc; transition: box-shadow .15s, border-color .15s; }
    #modal_payment .unified-payment-section:focus-within { border-top-color: #00a65a; box-shadow: 0 4px 16px rgba(0,0,0,.14); }
    #modal_payment .unified-payment-method { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    #modal_payment .unified-payment-quick-actions { display: flex; flex-wrap: wrap; margin: -5px 0 12px; }
    #modal_payment .unified-payment-quick-actions .btn { min-height: 38px; }
    #modal_payment .unified-payment-amount, #modal_payment .cash-tendered { font-size: 22px; font-weight: 700; }
    @media (max-width: 767px) {
        #modal_payment .modal-dialog { width: calc(100% - 10px); margin: 5px; }
        #modal_payment .modal-body { max-height: calc(100vh - 130px); padding: 10px; }
    }
</style>

<!-- Used for express checkout payment methods that only require a transaction number -->
<div class="modal fade" tabindex="-1" role="dialog" id="transaction_no_modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-mobile"></i> @lang('lang_v1.mpesa_checkout')</h4>
            </div>
            <div class="modal-body">
                @php
                    $is_intasend_enabled = $is_intasend_enabled ?? (!empty($enabled_modules) && in_array('intasend', $enabled_modules));
                    $is_daraja_enabled = $is_daraja_enabled ?? (!empty($enabled_modules) && in_array('daraja', $enabled_modules));
                    $is_mpesa_gateway_enabled = $is_intasend_enabled || $is_daraja_enabled;
                @endphp
                @if($is_mpesa_gateway_enabled)
                    <div id="mpesa_stk_status" class="alert hide" role="alert"></div>
                    @if($is_intasend_enabled)
                        <input type="hidden" id="intasend_pos_search_url" value="{{ route('intasend.pos_search') }}">
                        <input type="hidden" id="intasend_stk_push_url" value="{{ route('intasend.stk_push') }}">
                    @endif
                    @if($is_daraja_enabled)
                        <input type="hidden" id="daraja_pos_search_url" value="{{ route('daraja.pos_search') }}">
                        <input type="hidden" id="daraja_stk_push_url" value="{{ route('daraja.stk_push') }}">
                    @endif
                    <div class="well well-sm">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! Form::label('intasend_stk_phone_number', __('lang_v1.phone_number')) !!}
                                    {!! Form::text('', null, [
                                        'class' => 'form-control input-lg',
                                        'placeholder' => __('lang_v1.phone_number'),
                                        'id' => 'intasend_stk_phone_number',
                                        'autocomplete' => 'off',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! Form::label('intasend_stk_amount', __('sale.amount')) !!}
                                    {!! Form::text('', null, [
                                        'class' => 'form-control input-lg input_number',
                                        'placeholder' => __('sale.amount'),
                                        'id' => 'intasend_stk_amount',
                                        'autocomplete' => 'off',
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            @if($is_intasend_enabled)
                                <button type="button" class="btn btn-primary mpesa-stk-action" data-provider="intasend"><i class="fa fa-mobile"></i> @lang('lang_v1.send_intasend_stk_push')</button>
                                <button type="button" class="btn btn-default mpesa-search-action" data-provider="intasend"><i class="fa fa-search"></i> @lang('lang_v1.search_intasend_collections')</button>
                            @endif
                            @if($is_daraja_enabled)
                                <button type="button" class="btn btn-success mpesa-stk-action" data-provider="daraja"><i class="fa fa-mobile"></i> @lang('lang_v1.send_daraja_stk_push')</button>
                                <button type="button" class="btn btn-default mpesa-search-action" data-provider="daraja"><i class="fa fa-search"></i> @lang('lang_v1.search_daraja_payments')</button>
                            @endif
                        </div>
                    </div>
                    <div id="intasend_pos_candidates" class="table-responsive hide">
                        <p class="help-block">@lang('lang_v1.mpesa_multiple_selection_help')</p>
                        <table class="table table-condensed table-bordered">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.mpesa_transaction_no')</th>
                                    <th>@lang('lang_v1.phone_number')</th>
                                    <th>@lang('sale.amount')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="alert alert-info hide mpesa_excess_credit_option">
                        <label>
                            <input type="checkbox" class="store_mpesa_excess_as_advance_toggle">
                            @lang('lang_v1.store_mpesa_excess_as_credit')
                            (<span class="mpesa_excess_credit_amount"></span>)
                        </label>
                        <div><small>@lang('lang_v1.store_mpesa_excess_as_credit_help')</small></div>
                    </div>
                @endif
                <div class="form-group">
                    {!! Form::label('express_transaction_no', __('lang_v1.mpesa_transaction_no')) !!}
                    {!! Form::text('', null, [
                        'class' => 'form-control',
                        'placeholder' => __('lang_v1.transaction_no'),
                        'id' => 'express_transaction_no',
                        'autocomplete' => 'off',
                    ]) !!}
                    @if($is_mpesa_gateway_enabled)
                        <p class="help-block">@lang('lang_v1.intasend_pos_transaction_code_help')</p>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save-transaction-no">@lang('sale.finalize_payment')</button>
            </div>
        </div>
    </div>
</div>

<!-- Used for express checkout card transaction -->
<div class="modal fade" tabindex="-1" role="dialog" id="card_details_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('lang_v1.card_transaction_details')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_number', __('lang_v1.card_no')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_no'),
                                    'id' => 'card_number',
                                    'autofocus',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_holder_name', __('lang_v1.card_holder_name')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_holder_name'),
                                    'id' => 'card_holder_name',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_transaction_number', __('lang_v1.card_transaction_no')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_transaction_no'),
                                    'id' => 'card_transaction_number',
                                ]) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_type', __('lang_v1.card_type')) !!}
                                {!! Form::select('', ['visa' => 'Visa', 'master' => 'MasterCard'], 'visa', [
                                    'class' => 'form-control select2',
                                    'id' => 'card_type',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_month', __('lang_v1.month')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.month'),
                                    'id' => 'card_month',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_year', __('lang_v1.year')) !!}
                                {!! Form::text('', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.year'), 'id' => 'card_year']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_security', __('lang_v1.security_code')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.security_code'),
                                    'id' => 'card_security',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save-card">@lang('sale.finalize_payment')</button>
            </div>
        </div>
    </div>
</div>

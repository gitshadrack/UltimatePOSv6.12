@php
    $method = $payment_line['method'];
    $is_cash = $method === 'cash';
    $is_mpesa = $method === 'custom_pay_1';
    $icon = $is_cash ? 'fa-money-bill-alt' : ($is_mpesa ? 'fa-mobile-alt' : ($method === 'card' ? 'fa-credit-card' : ($method === 'bank_transfer' ? 'fa-university' : 'fa-wallet')));
    $reference = $method === 'card'
        ? ($payment_line['card_transaction_number'] ?? '')
        : ($method === 'cheque'
            ? ($payment_line['cheque_number'] ?? '')
            : ($method === 'bank_transfer'
                ? ($payment_line['bank_account_number'] ?? '')
                : ($payment_line['transaction_no'] ?? '')));
@endphp

<div class="col-md-6 unified-payment-column" data-payment-method="{{ $method }}">
    <section class="box box-solid payment_row unified-payment-section">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $payment_label }}</h3>
        </div>
        <div class="box-body">
            <input type="hidden" class="payment_row_index" value="{{ $row_index }}">
            @if (!empty($payment_line['id']))
                {!! Form::hidden("payment[$row_index][payment_id]", $payment_line['id']) !!}
            @endif
            <select name="payment[{{ $row_index }}][method]" class="payment_types_dropdown unified-payment-method" aria-hidden="true" tabindex="-1">
                <option value="{{ $method }}" selected>{{ $payment_label }}</option>
            </select>

            <div class="form-group">
                {!! Form::label("amount_$row_index", __('sale.amount')) !!}
                <div class="input-group input-group-lg">
                    <span class="input-group-addon">{{ session('currency')['symbol'] ?? '' }}</span>
                    {!! Form::text("payment[$row_index][amount]", @num_format($payment_line['amount'] ?? 0), [
                        'class' => 'form-control payment-amount input_number unified-payment-amount',
                        'id' => "amount_$row_index",
                        'inputmode' => 'decimal',
                        'autocomplete' => 'off',
                    ]) !!}
                </div>
            </div>

            <div class="btn-group btn-group-sm unified-payment-quick-actions" role="group">
                <button type="button" class="btn btn-default unified-pay-remaining">@lang('lang_v1.pay_remaining')</button>
                @if ($is_cash)
                    <button type="button" class="btn btn-default unified-cash-exact">@lang('lang_v1.exact')</button>
                    @foreach ([50, 100, 500, 1000] as $increment)
                        <button type="button" class="btn btn-default unified-cash-add" data-amount="{{ $increment }}">+{{ $increment }}</button>
                    @endforeach
                @endif
            </div>

            @if ($is_cash)
                <div class="form-group cash-tendered-container">
                    <label for="cash_tendered_{{ $row_index }}">@lang('lang_v1.amount_tendered')</label>
                    {!! Form::text("payment[$row_index][cash_tendered]", @num_format($payment_line['cash_tendered'] ?? $payment_line['amount'] ?? 0), [
                        'class' => 'form-control input-lg cash-tendered input_number',
                        'id' => "cash_tendered_$row_index",
                        'inputmode' => 'decimal',
                        'autocomplete' => 'off',
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.cash_tendered_help')</p>
                </div>
            @elseif ($method === 'card')
                <div class="form-group">
                    <label for="card_transaction_number_{{ $row_index }}">@lang('lang_v1.card_transaction_no')</label>
                    {!! Form::text("payment[$row_index][card_transaction_number]", $reference, ['class' => 'form-control input-lg payment-reference', 'id' => "card_transaction_number_$row_index", 'autocomplete' => 'off']) !!}
                </div>
            @elseif ($method === 'cheque')
                <div class="form-group">
                    <label for="cheque_number_{{ $row_index }}">@lang('lang_v1.cheque_no')</label>
                    {!! Form::text("payment[$row_index][cheque_number]", $reference, ['class' => 'form-control input-lg payment-reference', 'id' => "cheque_number_$row_index", 'autocomplete' => 'off']) !!}
                </div>
            @elseif ($method === 'bank_transfer')
                <div class="form-group">
                    <label for="bank_account_number_{{ $row_index }}">@lang('lang_v1.bank_account_number')</label>
                    {!! Form::text("payment[$row_index][bank_account_number]", $reference, ['class' => 'form-control input-lg payment-reference', 'id' => "bank_account_number_$row_index", 'autocomplete' => 'off']) !!}
                </div>
            @elseif (str_starts_with($method, 'custom_pay_'))
                @php $custom_index = (int) str_replace('custom_pay_', '', $method); @endphp
                @if ($is_mpesa && (($is_intasend_enabled ?? false) || ($is_daraja_enabled ?? false)))
                    <div class="form-group">
                        <label for="mpesa_phone_{{ $row_index }}">@lang('lang_v1.phone_number')</label>
                        <input type="tel" class="form-control input-lg unified-mpesa-phone" id="mpesa_phone_{{ $row_index }}"
                            placeholder="07XXXXXXXX" inputmode="tel" autocomplete="tel">
                    </div>
                @endif
                <div class="form-group">
                    <label for="transaction_no_{{ $custom_index }}_{{ $row_index }}">@lang('lang_v1.transaction_no')</label>
                    <div class="input-group input-group-lg">
                        {!! Form::text("payment[$row_index][transaction_no_$custom_index]", $reference, ['class' => 'form-control payment-reference', 'id' => "transaction_no_{$custom_index}_{$row_index}", 'autocomplete' => 'off']) !!}
                        @if ($is_mpesa && (($is_intasend_enabled ?? false) || ($is_daraja_enabled ?? false)))
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-success intasend-row-action" data-row_index="{{ $row_index }}" data-transaction_no_index="1">
                                    <i class="fa fa-mobile"></i> @lang('lang_v1.open_mpesa_tools')
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
                @if ($is_mpesa && (($is_intasend_enabled ?? false) || ($is_daraja_enabled ?? false)))
                    <div class="form-group">
                        @if ($is_intasend_enabled ?? false)
                            <button type="button" class="btn btn-primary btn-lg mpesa-stk-action" data-provider="intasend"><i class="fa fa-mobile"></i> @lang('lang_v1.send_intasend_stk_push')</button>
                        @endif
                        @if ($is_daraja_enabled ?? false)
                            <button type="button" class="btn btn-success btn-lg mpesa-stk-action" data-provider="daraja"><i class="fa fa-mobile"></i> @lang('lang_v1.send_daraja_stk_push')</button>
                        @endif
                    </div>
                    <div class="alert alert-info unified-mpesa-status" role="status">@lang('lang_v1.ready')</div>
                @endif
            @else
                <div class="form-group">
                    <label for="note_{{ $row_index }}">@lang('sale.payment_note')</label>
                    {!! Form::text("payment[$row_index][note]", $payment_line['note'] ?? '', ['class' => 'form-control input-lg payment-reference', 'id' => "note_$row_index", 'autocomplete' => 'off']) !!}
                </div>
            @endif

            @if (!empty($accounts) && $method !== 'advance')
                <div class="form-group">
                    <label for="account_{{ $row_index }}">@lang('lang_v1.payment_account')</label>
                    {!! Form::select("payment[$row_index][account_id]", $accounts, $payment_line['account_id'] ?? '', ['class' => 'form-control select2 account-dropdown', 'id' => "account_$row_index", 'style' => 'width:100%']) !!}
                </div>
            @endif
        </div>
    </section>
</div>

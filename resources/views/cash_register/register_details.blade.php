<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header mini_print">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.register_details' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} -  {{\Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('jS M, Y h:i A')}} )</h3>
    </div>

    <div class="modal-body">
      @include('cash_register.payment_details')
      @if(!empty($register_details->total_mpesa) && $register_details->total_mpesa != 0)
        <hr>
        <div class="row">
          <div class="col-md-8 col-sm-12">
            <h3>@lang('lang_v1.mpesa_verification')</h3>
            <table class="table table-slim">
              <tbody>
                <tr>
                  <th>@lang('lang_v1.total_mpesa')</th>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_mpesa }}</span></td>
                </tr>
                <tr>
                  <th>@lang('lang_v1.verified_match')</th>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->verified_mpesa }}</span></td>
                </tr>
                <tr>
                  <th>@lang('lang_v1.pending_verification')</th>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->pending_mpesa }}</span></td>
                </tr>
                <tr>
                  <th>@lang('lang_v1.rejected_msg')</th>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->rejected_mpesa }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        @if(!empty($mpesa_audit_trail) && $mpesa_audit_trail->count())
          <div class="row">
            <div class="col-md-12 col-sm-12">
              <h3>M-PESA Audit Trail</h3>
              <div class="table-responsive">
                <table class="table table-slim table-bordered">
                  <thead>
                    <tr>
                      <th>@lang('lang_v1.date')</th>
                      <th>@lang('sale.invoice_no')</th>
                      <th>M-PESA Reference</th>
                      <th>@lang('sale.customer_name')</th>
                      <th>@lang('sale.amount')</th>
                      <th>@lang('sale.status')</th>
                      <th>Audited By</th>
                      <th>Audited At</th>
                      <th>@lang('brand.note')</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($mpesa_audit_trail as $payment)
                      @php
                        $status = $payment->mpesa_verification_status ?: 'pending';
                        $status_class = [
                          'verified' => 'success',
                          'rejected' => 'danger',
                          'pending' => 'warning',
                        ][$status] ?? 'default';
                        $amount = !empty($payment->is_return) ? -1 * $payment->amount : $payment->amount;
                        $customer_name = !empty($payment->supplier_business_name) ? $payment->supplier_business_name : $payment->customer;
                      @endphp
                      <tr>
                        <td>{{ @format_datetime($payment->paid_on ?? $payment->transaction_date) }}</td>
                        <td>{{ $payment->invoice_no }}</td>
                        <td>{{ $payment->transaction_no ?: $payment->note }}</td>
                        <td>{{ $customer_name }}</td>
                        <td><span class="display_currency" data-currency_symbol="true">{{ $amount }}</span></td>
                        <td><span class="label label-{{ $status_class }}">{{ ucfirst($status) }}</span></td>
                        <td>{{ trim($payment->verifier_name) ?: '--' }}</td>
                        <td>@if(!empty($payment->mpesa_verified_at)){{ @format_datetime($payment->mpesa_verified_at) }}@else -- @endif</td>
                        <td>{{ $payment->mpesa_verification_note ?: '--' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif
      @endif
      <hr>
      @if(!empty($register_details->denominations))
        @php
          $total = 0;
        @endphp
        <div class="row">
          <div class="col-md-8 col-sm-12">
            <h3>@lang( 'lang_v1.cash_denominations' )</h3>
            <table class="table table-slim">
              <thead>
                <tr>
                  <th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-center">@lang('lang_v1.count')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-left">@lang('sale.subtotal')</th>
                </tr>
              </thead>
              <tbody>
                @foreach($register_details->denominations as $key => $value)
                <tr>
                  <td class="text-right">{{$key}}</td>
                  <td class="text-center">X</td>
                  <td class="text-center">{{$value ?? 0}}</td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    @format_currency($key * $value)
                  </td>
                </tr>
                @php $total += ((float) $key) * ((float) $value); @endphp
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center">@lang('sale.total')</th>
                  <td>@format_currency($total)</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      @endif
      
      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
          <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>

    <div class="modal-footer">
  <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print print-mini-button" 
          aria-label="Print">
      <i class="fa fa-print"></i> @lang('messages.print_mini')
  </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
        aria-label="Print" 
          onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print_detailed' )
      </button>

      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" 
        data-dismiss="modal">@lang( 'messages.cancel' )
      </button>
    </div>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<style type="text/css">
  @media print {
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        overflow: visible!important;
    }
}
</style>
<script>
  $(document).ready(function () {
      $(document).on('click', '.print-mini-button', function () {
          $('.mini_print').printThis();
      });
  });
</script>

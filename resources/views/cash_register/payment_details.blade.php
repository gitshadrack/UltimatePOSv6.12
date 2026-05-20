@php
  $money = function ($amount) {
      return '<span class="display_currency" data-currency_symbol="true">'.($amount ?? 0).'</span>';
  };

  $gross_sales = $details['transaction_details']->total_sales ?? 0;
  $total_refund = $register_details->total_refund ?? 0;
  $total_collected = $register_details->total_sale ?? 0;
  $total_due_collected = 0;
  $net_sales = $gross_sales - $total_refund;
  $pending_from_customers = max($net_sales - $total_collected - $total_due_collected, 0);

  $payment_rows = [
      'cash' => [
          'label' => __('cash_register.cash_payment'),
          'sell' => $register_details->total_cash ?? 0,
          'due' => 0,
          'expense' => $register_details->total_cash_expense ?? 0,
      ],
      'cheque' => [
          'label' => __('cash_register.checque_payment'),
          'sell' => $register_details->total_cheque ?? 0,
          'due' => 0,
          'expense' => $register_details->total_cheque_expense ?? 0,
      ],
      'card' => [
          'label' => __('cash_register.card_payment'),
          'sell' => $register_details->total_card ?? 0,
          'due' => 0,
          'expense' => $register_details->total_card_expense ?? 0,
      ],
      'bank_transfer' => [
          'label' => __('cash_register.bank_transfer'),
          'sell' => $register_details->total_bank_transfer ?? 0,
          'due' => 0,
          'expense' => $register_details->total_bank_transfer_expense ?? 0,
      ],
      'advance' => [
          'label' => __('lang_v1.advance_payment'),
          'sell' => $register_details->total_advance ?? 0,
          'due' => 0,
          'expense' => $register_details->total_advance_expense ?? 0,
      ],
  ];

  for ($i = 1; $i <= 7; $i++) {
      $key = 'custom_pay_'.$i;
      if (array_key_exists($key, $payment_types)) {
          $payment_rows[$key] = [
              'label' => $payment_types[$key],
              'sell' => $register_details->{'total_'.$key} ?? 0,
              'due' => 0,
              'expense' => $register_details->{'total_'.$key.'_expense'} ?? 0,
          ];
      }
  }

  $payment_rows['other'] = [
      'label' => __('cash_register.other_payments'),
      'sell' => $register_details->total_other ?? 0,
      'due' => 0,
      'expense' => $register_details->total_other_expense ?? 0,
  ];

  $visible_payment_rows = array_filter($payment_rows, function ($row) {
      return ($row['sell'] ?? 0) != 0 || ($row['due'] ?? 0) != 0 || ($row['expense'] ?? 0) != 0;
  });

  if (empty($visible_payment_rows)) {
      $visible_payment_rows = ['cash' => $payment_rows['cash']];
  }

  $total_payment_sell = array_sum(array_column($payment_rows, 'sell'));
  $total_payment_due = array_sum(array_column($payment_rows, 'due'));
  $total_payment_expense = array_sum(array_column($payment_rows, 'expense'));
  $grand_total_collected = $total_payment_sell + $total_payment_due;

  $expected_cash_in_drawer = ($register_details->cash_in_hand ?? 0)
      + ($register_details->total_cash ?? 0)
      - ($register_details->total_cash_refund ?? 0)
      - ($register_details->total_cash_expense ?? 0);
@endphp

<div class="register-report-print mini_print">
  <section class="register-report-section">
    <h3>Cash Drawer Summary</h3>
    <table class="table register-summary-table">
      <tbody>
        <tr>
          <td>Opening Balance:</td>
          <td>{!! $money($register_details->cash_in_hand ?? 0) !!}</td>
        </tr>
        <tr>
          <td>(+) Cash Received (Sales):</td>
          <td>{!! $money($register_details->total_cash ?? 0) !!}</td>
        </tr>
        <tr>
          <td>(+) Cash Received (Due Payment):</td>
          <td>{!! $money(0) !!}</td>
        </tr>
        <tr>
          <td>(-) Cash Refunds:</td>
          <td>{!! $money($register_details->total_cash_refund ?? 0) !!}</td>
        </tr>
        <tr>
          <td>(-) Cash Expenses:</td>
          <td>{!! $money($register_details->total_cash_expense ?? 0) !!}</td>
        </tr>
        <tr class="register-total-row">
          <th>Expected Cash in Drawer:</th>
          <th>{!! $money($expected_cash_in_drawer) !!}</th>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="register-report-section">
    <h3>Sales Summary</h3>
    <table class="table register-summary-table">
      <tbody>
        <tr>
          <td>Gross Sales (Total Invoiced):</td>
          <td>{!! $money($gross_sales) !!}</td>
        </tr>
        <tr>
          <td>(-) Total Refund:</td>
          <td>{!! $money($total_refund) !!}</td>
        </tr>
        <tr class="register-total-row">
          <th>Net Sales:</th>
          <th>{!! $money($net_sales) !!}</th>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="register-report-section">
    <h3>Payments Collected</h3>
    <table class="table register-payment-table">
      <thead>
        <tr>
          <th>Payment Method</th>
          <th>Sell</th>
          <th>Due Payments Collected</th>
          <th>Expense</th>
        </tr>
      </thead>
      <tbody>
        @foreach($visible_payment_rows as $row)
          <tr>
            <td>{{ $row['label'] }}:</td>
            <td>{!! $money($row['sell']) !!}</td>
            <td>{!! $money($row['due']) !!}</td>
            <td>{!! $money($row['expense']) !!}</td>
          </tr>
        @endforeach
        <tr class="register-total-row">
          <th>Total</th>
          <th>{!! $money($total_payment_sell) !!}</th>
          <th>{!! $money($total_payment_due) !!}</th>
          <th>{!! $money($total_payment_expense) !!}</th>
        </tr>
        <tr class="register-grand-total-row">
          <th>Grand Total Collected:</th>
          <th colspan="3">{!! $money($grand_total_collected) !!}</th>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="register-report-section">
    <h3>Outstanding Receivables</h3>
    <table class="table register-summary-table">
      <tbody>
        <tr>
          <td>Gross Sales (Total Invoiced):</td>
          <td>{!! $money($gross_sales) !!}</td>
        </tr>
        <tr>
          <td>(-) Total Collected:</td>
          <td>{!! $money($total_collected) !!}</td>
        </tr>
        <tr>
          <td>(-) Due Collected (for this register's sales):</td>
          <td>{!! $money($total_due_collected) !!}</td>
        </tr>
        <tr class="register-total-row">
          <th>Pending from Customers:</th>
          <th>{!! $money($pending_from_customers) !!}</th>
        </tr>
      </tbody>
    </table>
  </section>
</div>

<style>
  .register-report-print {
    color: #111;
    font-size: 15px;
    line-height: 1.45;
  }

  .register-report-section {
    border-top: 1px solid #777;
    margin-top: 24px;
    padding-top: 16px;
  }

  .register-report-section:first-child {
    border-top: 0;
    margin-top: 0;
    padding-top: 0;
  }

  .register-report-section h3 {
    border-bottom: 2px solid #333;
    color: #000;
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 10px;
    padding-bottom: 5px;
  }

  .register-report-print .table {
    margin-bottom: 0;
  }

  .register-report-print .table > tbody > tr > td,
  .register-report-print .table > tbody > tr > th,
  .register-report-print .table > thead > tr > th {
    border-top: 1px solid #eee;
    padding: 8px 8px;
    vertical-align: middle;
  }

  .register-report-print .table > thead > tr > th {
    border-top: 0;
    font-weight: 700;
  }

  .register-summary-table td:last-child,
  .register-summary-table th:last-child {
    text-align: left;
    width: 28%;
  }

  .register-payment-table th,
  .register-payment-table td {
    text-align: left;
  }

  .register-payment-table .register-total-row > th {
    border-top: 2px solid #333 !important;
  }

  .register-total-row th {
    font-weight: 700;
  }

  .register-grand-total-row th {
    border-top: 0 !important;
    font-size: 16px;
    font-weight: 700;
  }

  @media print {
    .register-report-print {
      font-size: 13px;
    }

    .register-report-section {
      break-inside: avoid;
      page-break-inside: avoid;
    }

    .register-report-section h3 {
      font-size: 18px;
    }
  }
</style>

@include('cash_register.register_product_details')

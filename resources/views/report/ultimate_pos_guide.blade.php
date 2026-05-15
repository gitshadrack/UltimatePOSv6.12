@extends('layouts.app')
@section('title', 'Ultimate POS Guide')

@section('content')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Ultimate POS Guide</h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-3">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Contents</h3>
                </div>
                <div class="box-body">
                    <ul class="nav nav-pills nav-stacked">
                        <li><a href="#products">Products</a></li>
                        <li><a href="#stock">Stock</a></li>
                        <li><a href="#sales">Sales</a></li>
                        <li><a href="#payments">Payments</a></li>
                        <li><a href="#reports">Reports</a></li>
                        <li><a href="#daily">Daily closing</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="box box-primary" id="products">
                <div class="box-header with-border">
                    <h3 class="box-title">1. Add Products</h3>
                </div>
                <div class="box-body">
                    <ol>
                        <li>Go to <strong>Products &gt; Add Product</strong>.</li>
                        <li>Enter product name, SKU or barcode, category, brand, unit, and selling price.</li>
                        <li>Enable stock management if you want the system to track quantity.</li>
                        <li>Select the business location where the product is available.</li>
                        <li>Save the product.</li>
                    </ol>
                    <p class="help-block">
                        If you are importing products, make sure category, brand, unit, location, and stock columns match the import template.
                    </p>
                </div>
            </div>

            <div class="box box-primary" id="stock">
                <div class="box-header with-border">
                    <h3 class="box-title">2. Add or Correct Stock</h3>
                </div>
                <div class="box-body">
                    <h4>Opening stock</h4>
                    <ol>
                        <li>When adding or editing a product, enter opening stock for each location.</li>
                        <li>Use this when you are starting with existing stock in the shop.</li>
                    </ol>

                    <h4>Purchases</h4>
                    <ol>
                        <li>Go to <strong>Purchases &gt; Add Purchase</strong>.</li>
                        <li>Select supplier, location, product, quantity, and purchase price.</li>
                        <li>Finalize the purchase to increase stock.</li>
                    </ol>

                    <h4>Stock adjustment</h4>
                    <ol>
                        <li>Go to <strong>Stock Adjustment</strong>.</li>
                        <li>Use normal adjustment for damaged, missing, expired, or counted stock differences.</li>
                        <li>Stock adjustment reduces stock and keeps an audit record.</li>
                    </ol>
                </div>
            </div>

            <div class="box box-primary" id="sales">
                <div class="box-header with-border">
                    <h3 class="box-title">3. Sell Products</h3>
                </div>
                <div class="box-body">
                    <ol>
                        <li>Go to <strong>Sell &gt; POS</strong> or <strong>Sell &gt; Add Sale</strong>.</li>
                        <li>Select customer and location.</li>
                        <li>Search or scan products, then confirm quantity and price.</li>
                        <li>Choose payment method such as cash, card, M-PESA, or multiple pay.</li>
                        <li>Finalize the sale to reduce stock and record income.</li>
                    </ol>
                    <p class="help-block">
                        If a cashier should only finalize payment, create the sale as draft or quotation first, then let the cashier open and complete it.
                    </p>
                </div>
            </div>

            <div class="box box-primary" id="payments">
                <div class="box-header with-border">
                    <h3 class="box-title">4. Record Payments</h3>
                </div>
                <div class="box-body">
                    <ol>
                        <li>Use <strong>Cash</strong> for cash received at the register.</li>
                        <li>Use <strong>Card</strong> for bank card payments and enter card details where required.</li>
                        <li>Use <strong>M-PESA</strong> or custom payment for mobile money transaction numbers.</li>
                        <li>Use <strong>Multiple Pay</strong> when the customer pays with more than one method.</li>
                    </ol>
                    <p class="help-block">
                        Payment reports depend on the payment method entered during checkout, so cashiers should choose the correct method every time.
                    </p>
                </div>
            </div>

            <div class="box box-primary" id="reports">
                <div class="box-header with-border">
                    <h3 class="box-title">5. View Reports</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Report</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Purchase &amp; Sell Report</strong></td>
                                    <td>Compare purchases, sales, due amounts, and overall movement.</td>
                                </tr>
                                <tr>
                                    <td><strong>Stock Report</strong></td>
                                    <td>Check current stock by product and location.</td>
                                </tr>
                                <tr>
                                    <td><strong>Product Sell Report</strong></td>
                                    <td>See which products were sold, with quantity, invoice, date, customer, and payment method.</td>
                                </tr>
                                <tr>
                                    <td><strong>Product Purchase Report</strong></td>
                                    <td>Review purchased products, supplier, quantity, and cost.</td>
                                </tr>
                                <tr>
                                    <td><strong>Register Report</strong></td>
                                    <td>Review cashier register totals and payment collections.</td>
                                </tr>
                                <tr>
                                    <td><strong>Stock Adjustment Report</strong></td>
                                    <td>Audit stock reductions from adjustments.</td>
                                </tr>
                                <tr>
                                    <td><strong>Profit / Loss Report</strong></td>
                                    <td>Review gross profit, expenses, net profit, and stock value.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="box box-primary" id="daily">
                <div class="box-header with-border">
                    <h3 class="box-title">6. Daily Closing</h3>
                </div>
                <div class="box-body">
                    <ol>
                        <li>Cashier opens own register before selling.</li>
                        <li>At end of shift, cashier reviews cash, card, M-PESA, and other payment totals.</li>
                        <li>Cashier closes own register.</li>
                        <li>Manager checks Register Report, Sell Payment Report, and Product Sell Report.</li>
                        <li>Any stock difference should be recorded using Stock Adjustment.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

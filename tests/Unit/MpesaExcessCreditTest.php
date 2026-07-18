<?php

namespace Tests\Unit;

use App\Events\TransactionPaymentAdded;
use App\Listeners\AddAccountTransaction;
use App\Listeners\DeleteAccountTransaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Mockery;
use Tests\TestCase;

class MpesaExcessCreditTest extends TestCase
{
    public function test_advance_change_return_adds_customer_credit()
    {
        $transaction_util = Mockery::mock(TransactionUtil::class);
        $transaction_util->shouldReceive('updateContactBalance')
            ->once()
            ->with(25, 10.0, 'add');

        $module_util = Mockery::mock(ModuleUtil::class);
        $module_util->shouldReceive('isModuleEnabled')
            ->once()
            ->with('account', 7)
            ->andReturn(false);

        $payment = new TransactionPayment();
        $payment->method = 'advance';
        $payment->amount = 10;
        $payment->is_return = 1;
        $payment->payment_for = 25;
        $payment->business_id = 7;

        (new AddAccountTransaction($transaction_util, $module_util))
            ->handle(new TransactionPaymentAdded($payment, ['transaction_type' => 'sell']));
    }

    public function test_using_advance_on_a_future_sale_deducts_customer_credit()
    {
        $transaction_util = Mockery::mock(TransactionUtil::class);
        $transaction_util->shouldReceive('updateContactBalance')
            ->once()
            ->with(25, 10.0, 'deduct');

        $module_util = Mockery::mock(ModuleUtil::class);
        $module_util->shouldReceive('isModuleEnabled')
            ->once()
            ->with('account', 7)
            ->andReturn(false);

        $payment = new TransactionPayment();
        $payment->method = 'advance';
        $payment->amount = 10;
        $payment->is_return = 0;
        $payment->payment_for = 25;
        $payment->business_id = 7;

        (new AddAccountTransaction($transaction_util, $module_util))
            ->handle(new TransactionPaymentAdded($payment, ['transaction_type' => 'sell']));
    }

    public function test_deleting_advance_change_return_removes_customer_credit()
    {
        $transaction_util = Mockery::mock(TransactionUtil::class);
        $transaction_util->shouldReceive('updateContactBalance')
            ->once()
            ->with(25, 10.0, 'deduct');

        $module_util = Mockery::mock(ModuleUtil::class);
        $module_util->shouldReceive('isModuleEnabled')
            ->once()
            ->with('account')
            ->andReturn(false);

        $payment = new TransactionPayment();
        $payment->method = 'advance';
        $payment->amount = 10;
        $payment->is_return = 1;
        $payment->payment_for = 25;

        $event = new \stdClass();
        $event->transactionPayment = $payment;

        (new DeleteAccountTransaction($transaction_util, $module_util))->handle($event);
    }
}

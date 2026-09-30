<?php

namespace Tests\Unit;

use App\Utils\ZReportUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ZReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.z_report_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('z_report_test');
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->default(1);
            $table->integer('location_id')->default(10);
            $table->integer('created_by')->default(5);
            $table->string('type')->default('sell');
            $table->string('status')->default('final');
            $table->dateTime('transaction_date')->default('2026-09-08 12:00:00');
            $table->decimal('final_total', 22, 4)->default(100);
            $table->decimal('tax_amount', 22, 4)->default(10);
        });
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id');
            $table->string('method')->default('cash');
            $table->decimal('amount', 22, 4);
            $table->boolean('is_return')->default(false);
            $table->dateTime('paid_on')->default('2026-09-08 12:00:00');
        });
    }

    public function test_totals_exclude_drafts_other_tenants_locations_users_and_next_day()
    {
        foreach ([[], ['type' => 'sell_return', 'final_total' => 20, 'tax_amount' => 2],
            ['type' => 'expense', 'final_total' => 15], ['type' => 'expense_refund', 'final_total' => 3],
            ['status' => 'draft'], ['business_id' => 2], ['location_id' => 20], ['created_by' => 6],
            ['transaction_date' => '2026-09-09 00:00:00']] as $row) {
            DB::table('transactions')->insert($row ?: ['type' => 'sell']);
        }
        $report = (new ZReportUtil())->getSummary(1, [10], '2026-09-08', 5);
        $this->assertSame(1, $report['sales_count']);
        $this->assertSame(100.0, $report['sales_total']);
        $this->assertSame(80.0, $report['net_sales']);
        $this->assertSame(8.0, $report['net_tax']);
        $this->assertSame(12.0, $report['expenses_total']);
        $empty = (new ZReportUtil())->getSummary(1, [], '2026-09-08');
        $this->assertSame(0, $empty['sales_count']);
    }

    public function test_payments_use_payment_date_and_deduct_change_and_returns_without_multiplying_sales()
    {
        $sale = DB::table('transactions')->insertGetId(['type' => 'sell']);
        $oldSale = DB::table('transactions')->insertGetId(['transaction_date' => '2026-09-07 12:00:00']);
        $return = DB::table('transactions')->insertGetId(['type' => 'sell_return', 'final_total' => 20]);
        foreach ([[$sale, 80, 0], [$sale, 30, 0], [$sale, 10, 1], [$oldSale, 50, 0], [$return, 20, 0]] as [$id, $amount, $isReturn]) {
            DB::table('transaction_payments')->insert(['transaction_id' => $id, 'amount' => $amount, 'is_return' => $isReturn]);
        }
        DB::table('transaction_payments')->insert(['transaction_id' => $sale, 'amount' => 999, 'paid_on' => '2026-09-09 00:00:00']);
        $report = (new ZReportUtil())->getSummary(1, [10], '2026-09-08');
        $this->assertSame(100.0, $report['sales_total']);
        $this->assertEquals(130, $report['payments']->first()->total);
    }
}

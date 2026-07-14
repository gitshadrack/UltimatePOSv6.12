<?php
/**
 * Damage Management Module
 * 
 * Comprehensive Damage Management Module - Track damaged products, manage dispatches 
 * to suppliers, and handle compensation claims with full documentation and reporting.
 * 
 * Module: DamageManagement
 * Author: Hackermiind
 * Version: 1.0.0
 * 
 * This is a complete free module for non commercial use.
 * 
 * @package Modules\DamageManagement
 */

namespace Modules\DamageManagement\Entities;

use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Variation;
use App\Brands;
use App\Category;
use App\Unit;
use App\Contact;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DamageRecord extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'product_id',
        'variation_id',
        'brand_id',
        'category_id',
        'unit_id',
        'customer_id',
        'supplier_id',
        'reference_no',
        'dispatch_status',
        'approval_status',
        'approved_by',
        'approved_at',
        'reported_at',
        'quantity',
        'dispatched_quantity',
        'unit_purchase_price',
        'unit_sell_price',
        'purchase_value',
        'sell_value',
        'expected_compensation',
        'given_compensation',
        'compensation_basis',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'quantity' => 'float',
        'dispatched_quantity' => 'float',
        'unit_purchase_price' => 'float',
        'unit_sell_price' => 'float',
        'purchase_value' => 'float',
        'sell_value' => 'float',
        'expected_compensation' => 'float',
        'given_compensation' => 'float',
    ];

    protected $dates = ['reported_at'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function dispatchLines()
    {
        return $this->hasMany(DispatchDamageLine::class);
    }
    
    public function approvedBy()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('damage_records.business_id', $businessId);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return (float) max($this->quantity - $this->dispatched_quantity, 0);
    }

    public function markDispatched(float $quantity): void
    {
        $this->dispatched_quantity = ($this->dispatched_quantity ?? 0) + $quantity;

        if ($this->dispatched_quantity >= $this->quantity) {
            $this->dispatch_status = 'dispatched';
            $this->dispatched_quantity = $this->quantity;
        } elseif ($this->dispatched_quantity > 0) {
            $this->dispatch_status = 'partial';
        } else {
            $this->dispatch_status = 'not_dispatched';
        }

        $this->save();
    }

    public static function generateReference(Carbon $date, int $businessId): string
    {
        $prefix = 'DMG-'. $date->format('Ymd');
        $count = static::where('business_id', $businessId)
            ->whereDate('reported_at', $date->toDateString())
            ->count() + 1;

        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

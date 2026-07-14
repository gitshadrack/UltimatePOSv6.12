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
use App\User;
use Illuminate\Database\Eloquent\Model;

class DispatchDamage extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'transaction_id',
        'reference_no',
        'dispatched_at',
        'created_by',
        'total_purchase_value',
        'total_sell_value',
        'total_compensation_value',
        'notes',
        'status',
    ];
    
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }

    protected $casts = [
        'dispatched_at' => 'datetime',
        'total_purchase_value' => 'float',
        'total_sell_value' => 'float',
        'total_compensation_value' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(DispatchDamageLine::class);
    }

    public static function generateReference(\Carbon\Carbon $date, int $businessId): string
    {
        $prefix = 'DDG-'.$date->format('Ymd');
        $count = static::where('business_id', $businessId)
            ->whereDate('dispatched_at', $date->toDateString())
            ->count() + 1;

        return $prefix.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

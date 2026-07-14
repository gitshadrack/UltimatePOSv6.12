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

use Illuminate\Database\Eloquent\Model;

class DispatchDamageLine extends Model
{
    protected $fillable = [
        'dispatch_damage_id',
        'transaction_id',
        'damage_record_id',
        'dispatched_quantity',
        'purchase_value',
        'sell_value',
        'compensation_amount',
    ];
    
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }

    protected $casts = [
        'dispatched_quantity' => 'float',
        'purchase_value' => 'float',
        'sell_value' => 'float',
        'compensation_amount' => 'float',
    ];

    public function dispatch()
    {
        return $this->belongsTo(DispatchDamage::class, 'dispatch_damage_id');
    }

    public function damageRecord()
    {
        return $this->belongsTo(DamageRecord::class);
    }
}


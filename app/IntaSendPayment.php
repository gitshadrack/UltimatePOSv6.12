<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IntaSendPayment extends Model
{
    protected $table = 'intasend_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'is_attached' => 'boolean',
        'auto_attached' => 'boolean',
        'payload' => 'array',
        'amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'charges' => 'decimal:4',
        'attached_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'business_location_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class);
    }

    public function transaction_payment()
    {
        return $this->belongsTo(\App\TransactionPayment::class);
    }

    public function attached_user()
    {
        return $this->belongsTo(\App\User::class, 'attached_by');
    }
}

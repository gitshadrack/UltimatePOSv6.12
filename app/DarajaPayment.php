<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DarajaPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_attached' => 'boolean',
        'auto_attached' => 'boolean',
        'attached_at' => 'datetime',
        'transaction_date' => 'datetime',
        'request_payload' => 'array',
        'payload' => 'array',
    ];

    public function setting()
    {
        return $this->belongsTo(DarajaSetting::class, 'daraja_setting_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function transaction_payment()
    {
        return $this->belongsTo(TransactionPayment::class);
    }
}

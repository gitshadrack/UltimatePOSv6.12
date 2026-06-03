<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IntaSendSetting extends Model
{
    protected $table = 'intasend_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'require_webhook_signature' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'business_location_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }
}

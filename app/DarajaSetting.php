<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DarajaSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($setting) {
            $setting->callback_token = $setting->callback_token ?: Str::random(48);
        });
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function setConsumerKeyAttribute($value)
    {
        $this->attributes['consumer_key'] = $this->encryptCredential($value);
    }

    public function getConsumerKeyAttribute($value)
    {
        return $this->decryptCredential($value);
    }

    public function setConsumerSecretAttribute($value)
    {
        $this->attributes['consumer_secret'] = $this->encryptCredential($value);
    }

    public function getConsumerSecretAttribute($value)
    {
        return $this->decryptCredential($value);
    }

    public function setPasskeyAttribute($value)
    {
        $this->attributes['passkey'] = $this->encryptCredential($value);
    }

    public function getPasskeyAttribute($value)
    {
        return $this->decryptCredential($value);
    }

    protected function encryptCredential($value)
    {
        return $value === null || $value === '' ? null : Crypt::encryptString((string) $value);
    }

    protected function decryptCredential($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }
}

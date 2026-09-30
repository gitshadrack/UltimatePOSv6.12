<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfflineStockConflictResolution extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'vehicle_id',
        'owner_id',
        'vehicle_type',
        'brand',
        'model',
        'license_plate',
        'chassis_number',
        'owner_wallet_address',
        'sync_status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'vehicle_id', 'vehicle_id');
    }
}

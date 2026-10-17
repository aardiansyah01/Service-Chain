<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'vehicle_id',
        'owner_id',
        'owner_address',
        'vehicle_type',
        'brand',
        'model',
        'year',
        'license_plate',
        'vin',
        'color',
        'chassis_number',
        'owner_wallet_address',
        'sync_status',
        'registered_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'registered_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(
            Service::class,
            'vehicle_id',
            'vehicle_id'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $table = 'service_records';

    protected $fillable = [
        'service_id',
        'vehicle_id',
        'owner_id',
        'workshop_id',
        'workshop_name',
        'workshop_address',
        'service_type',
        'description',
        'service_date',
        'cost',
        'mileage',
        'receipt_cid',
        'receipt_path',
        'service_hash',
        'transaction_hash',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'cost' => 'decimal:2',
            'mileage' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            Vehicle::class,
            'vehicle_id',
            'vehicle_id'
        );
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
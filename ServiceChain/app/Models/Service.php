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
        'receipt_cid',
        'receipt_path',
        'service_hash',
        'transaction_hash',
        'sync_status',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class, 'workshop_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

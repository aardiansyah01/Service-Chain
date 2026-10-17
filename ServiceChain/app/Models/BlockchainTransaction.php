<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'transaction_hash',
        'contract_address',
        'block_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'block_number' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
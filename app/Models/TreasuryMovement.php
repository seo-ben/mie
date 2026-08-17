<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryMovement extends Model
{
    protected $fillable = [
        'agency_id',
        'processed_by',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'motive',
        'notes',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    // Relations
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

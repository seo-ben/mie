<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TontineCycle extends Model
{
    protected $fillable = [
        'tontine_account_id',
        'cycle_number',
        'start_date',
        'end_date',
        'target_amount',
        'collected_amount',
        'payout_amount',
        'status',
        'payout_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'payout_date' => 'date'
    ];

    public function tontineAccount()
    {
        return $this->belongsTo(TontineAccount::class);
    }

    
}

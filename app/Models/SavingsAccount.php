<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
{
    protected $fillable = [
        'account_id',
        'interest_rate',
        'minimum_balance',
        'monthly_fee'
    ];

    protected $casts = [
        'interest_rate' => 'decimal:4',
        'minimum_balance' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'last_interest_calculated' => 'datetime'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

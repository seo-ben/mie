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

    /**
     * Calcule et applique l'intérêt prorata (accrual quotidien)
     * Formule : (Solde * Taux) / 365
     */
    public function accrueDailyInterest()
    {
        $account = $this->account;
        if ($account->balance <= 0 || $this->interest_rate <= 0) return;

        $dailyRate = ($this->interest_rate / 100) / 365;
        $interestAmount = $account->balance * $dailyRate;

        if ($interestAmount < 0.01) return;

        \DB::transaction(function () use ($account, $interestAmount) {
            $balanceBefore = $account->balance;
            $account->increment('balance', $interestAmount);
            
            Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'interest', // Nature : Injection d'Intérêt
                'amount' => $interestAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $interestAmount,
                'status' => 'completed',
                'transaction_date' => now(),
                'transaction_reference' => 'INT-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'description' => 'Injection prorata d\'intérêt quotidien (Automatique)',
                'processed_at' => now(),
            ]);

            $this->update(['last_interest_at' => now()]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TontineAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'tontine_amount',
        'cycle_duration_months',
        'payment_frequency',
        'expected_monthly_payment',
        'total_expected',      // ✅ ajouté
        'total_paid',          // ✅ ajouté
        'penalty_rate',        // ✅ ajouté
        'total_penalties',     // ✅ ajouté
        'payout_amount',       // facultatif mais utile
    ];

    protected $casts = [
        'tontine_amount' => 'decimal:2',
        'expected_monthly_payment' => 'decimal:2',
        'total_expected' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'penalty_rate' => 'decimal:4',
        'total_penalties' => 'decimal:2',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'payout_date' => 'date'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cycles()
    {
        return $this->hasMany(TontineCycle::class);
    }
    public function activeCycle()
    {
        return $this->hasOne(TontineCycle::class)
                    ->where('status', 'active'); // ou 'en_cours' selon ta logique
    }

}

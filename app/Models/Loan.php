<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'loan_number',
        'client_id',
        'requested_amount',
        'approved_amount',
        'interest_rate',
        'duration_months',
        'purpose',
        'collateral_description',
        'status',
        'risk_level',
        'loan_type',
        'approved_amount',
        'monthly_payment',
        'total_amount_due',
        'outstanding_principal',
        'outstanding_interest',
        'total_paid',
        'penalty_amount',
        'days_overdue',
        'approved_by',
        'approved_at',
        'reviewed_by',
        'approval_notes'
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'monthly_payment' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
        'outstanding_principal' => 'decimal:2',
        'outstanding_interest' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'eligibility_score' => 'decimal:2',
        'application_date' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'first_payment_date' => 'date',
        'maturity_date' => 'date'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Accessor pour le montant restant
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, ($this->total_amount_due ?? 0) - ($this->total_paid ?? 0));
    }
}

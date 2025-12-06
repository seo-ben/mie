<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id',
        'payment_number',
        'due_date',
        'paid_date',
        'expected_amount',
        'principal_amount',
        'interest_amount',
        'payment_method',
        'penalty_amount',
        'paid_amount',
        'payment_reference',
        'processed_by',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'expected_amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

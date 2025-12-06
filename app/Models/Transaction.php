<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_reference',
        'account_id',
        'transaction_type',
        'amount',
        'payment_method',
        'withdrawal_fee',
        'fee_amount',
        'payment_reference',
        'mobile_money_operator',
        'description',
        'status',
        'balance_before',
        'balance_after',
        'processed_by', // ✅ à ajouter si manquant
        'processed_at',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'validation_required' => 'boolean',
        'processed_at' => 'datetime',
        'transaction_date' => 'datetime'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function receipt()
    {
        return $this->hasOne(TransactionReceipt::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function relatedAccount()
    {
        return $this->belongsTo(Account::class, 'related_account_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionReceipt extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'transaction_id',
        'receipt_number',
        'receipt_url',
        'receipt_type',
        'sent_via'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

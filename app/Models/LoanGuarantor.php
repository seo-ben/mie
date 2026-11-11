<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'guarantor_order',
        'full_name',
        'phone',
        'id_card_number',
        'relationship',
        'profession',
        'address',
        'collateral_value',
        'collateral_description',
        'is_verified',
        'verification_notes',
    ];

    protected $casts = [
        'guarantor_order'  => 'integer',
        'collateral_value' => 'decimal:2',
        'is_verified'      => 'boolean',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}


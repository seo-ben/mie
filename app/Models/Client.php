<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'client';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'client_number',
        'phone',
        'email',
        'password',
        'is_active',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'region',
        'profession',
        'monthly_income',
        'id_type',
        'id_number',
        'id_expiry_date',
        'profile_photo_url',
        'kyc_status',
        'kyc_approved_at',
        'kyc_approved_by',
        'registration_channel',
        'registration_type',
        'registration_status',
        'rejection_reason',
        'referred_by',
        'relationship',
        'registered_by',
        'agency_id',
        'credit_score',
        'validated_at',
        'is_leader_or_elected',
    ];

    protected $casts = [
        'date_of_birth'      => 'date',
        'id_expiry_date'     => 'date',
        'monthly_income'     => 'decimal:2',
        'credit_score'       => 'decimal:2',
        'kyc_approved_at'    => 'datetime',
        'validated_at'       => 'datetime',
        'is_active'          => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function savingsAccounts()
    {
        return $this->accounts()->where('account_type', 'savings');
    }

    public function tontineAccounts()
    {
        return $this->accounts()->where('account_type', 'tontine');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function documents()
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'kyc_approved_by');
    }

    public function referrer()
    {
        return $this->belongsTo(Client::class, 'referred_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeKycApproved($query)
    {
        return $query->where('kyc_status', 'approved');
    }

    public function scopeKycPending($query)
    {
        return $query->where('kyc_status', 'pending');
    }

    public function scopeKycRejected($query)
    {
        return $query->where('kyc_status', 'rejected');
    }

    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', 1);
    // }

    public function scopeActive($query)
    {
        return $query->where('registration_status', 'approved');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTotalSavingsAttribute()
    {
        return $this->savingsAccounts()->sum('balance');
    }

    public function getTotalTontineAttribute()
    {
        return $this->tontineAccounts()->sum('balance');
    }

    public function getIsEligibleForLoanAttribute()
    {
        return $this->kyc_status === 'approved'
            && $this->total_savings >= 50000
            && $this->loans()->whereIn('status', ['active', 'disbursed'])->count() === 0;
    }

    /**
     * Moteur d'Éligibilité : Analyse algorithmique du profil
     */
    public function calculateCreditScore(): float
    {
        $score = 0;

        // 1. Ancienneté (20 points)
        $monthsSinceRegistry = $this->created_at->diffInMonths(now());
        $score += min(20, $monthsSinceRegistry * 2);

        // 2. Volume d'Épargne relative au revenu (30 points)
        $totalAssets = $this->total_savings + $this->total_tontine;
        if ($this->monthly_income > 0) {
            $savingsRatio = $totalAssets / $this->monthly_income;
            $score += min(30, $savingsRatio * 10);
        }

        // 3. Discipline de Tontine (50 points)
        $tontineAccounts = $this->tontineAccounts()->withCount('transactions')->get();
        $totalTransactions = $tontineAccounts->sum('transactions_count');
        $score += min(50, $totalTransactions * 1.5);

        return min(100, $score);
    }

    /**
     * Artefact de Conformité KYC
     */
    public function getKycIsCompliantAttribute(): bool
    {
        if ($this->kyc_status !== 'approved') return false;
        if (!$this->kyc_expiry_date) return true; // Si pas de date, on assume permanent ou non-renseigné
        return $this->kyc_expiry_date->isFuture();
    }
}

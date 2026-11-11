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
        'max_tontine_accounts',
    ];

    protected $casts = [
        'date_of_birth'        => 'date',
        'id_expiry_date'       => 'date',
        'monthly_income'       => 'decimal:2',
        'credit_score'         => 'decimal:2',
        'kyc_approved_at'      => 'datetime',
        'validated_at'         => 'datetime',
        'is_active'            => 'boolean',
        'max_tontine_accounts' => 'integer',
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

    /**
     * Normalisation automatique du numéro de téléphone (format local à 8 chiffres au Togo)
     */
    public static function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '00228') && strlen($digits) > 8) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '228') && strlen($digits) > 8) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = self::normalizePhone($value);
    }

    /**
     * Vérifie si le client peut ouvrir un nouveau compte Tontine
     * (Par défaut max 1, sauf autorisation explicite de l'Admin)
     */
    public function canOpenTontineAccount(): bool
    {
        $maxAllowed = (int) ($this->max_tontine_accounts ?? 1);
        $activeCount = $this->tontineAccounts()->where('status', 'active')->count();
        return $activeCount < $maxAllowed;
    }

    /**
     * Vérifie si le client a un cycle de tontine de 31 jours en cours de cotisation non achevé.
     * Tant que le cycle de 31 jours n'est pas terminé, le client ne peut pas changer de mise ni passer à un autre carnet.
     */
    public function hasActiveUnfinishedTontineCycle(): bool
    {
        $accounts = $this->tontineAccounts()
            ->where('status', 'active')
            ->with(['tontineAccount.activeCycle'])
            ->get();

        foreach ($accounts as $acc) {
            $cycle = $acc->tontineAccount?->activeCycle;
            if ($cycle && $cycle->status === 'active' && $cycle->collected_amount > 0 && $cycle->collected_amount < $cycle->target_amount) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le client peut ouvrir un compte Épargne (strictement 1 seul compte actif)
     */
    public function canOpenSavingsAccount(): bool
    {
        $activeCount = $this->savingsAccounts()->where('status', 'active')->count();
        return $activeCount < 1;
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

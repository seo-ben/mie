<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'client_id',
        'account_type',
        'status',
        'activation_fee',
        'activation_fee_paid',
        'activation_payment_method',
        'activation_reference',
        'activated_at',
        'activated_by',
        'balance',
        'last_transaction_at',
        'created_by',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
    ];

    protected $casts = [
        'activation_fee' => 'decimal:2',
        'balance' => 'decimal:2',
        'activation_fee_paid' => 'boolean',
        'activated_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Relation avec le client propriétaire du compte
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation avec les transactions du compte
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relation avec l'utilisateur qui a activé le compte
     */
    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Relation avec l'utilisateur qui a créé le compte
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a suspendu le compte
     */
    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Relation avec les détails du compte d'épargne (si applicable)
     * Un compte peut avoir un seul compte d'épargne
     */
    public function savingsAccount()
    {
        return $this->hasOne(SavingsAccount::class);
    }

    /**
     * Relation avec les détails du compte tontine (si applicable)
     * Un compte peut avoir un seul compte tontine
     */
    public function tontineAccount()
    {
        return $this->hasOne(TontineAccount::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope pour les comptes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les comptes suspendus
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope pour les comptes en attente d'activation
     */
    public function scopePendingActivation($query)
    {
        return $query->where('status', 'pending_activation');
    }

    /**
     * Scope pour les comptes d'épargne
     */
    public function scopeSavings($query)
    {
        return $query->where('account_type', 'savings');
    }

    /**
     * Scope pour les comptes tontine
     */
    public function scopeTontine($query)
    {
        return $query->where('account_type', 'tontine');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Obtenir le type de compte formaté
     */
    public function getAccountTypeNameAttribute()
    {
        return $this->account_type === 'savings' ? 'Épargne' : 'Tontine';
    }

    /**
     * Obtenir le statut formaté
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'active' => 'Actif',
            'suspended' => 'Suspendu',
            'pending_activation' => 'En attente',
            'closed' => 'Fermé',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Vérifier si le compte est actif
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Vérifier si le compte est suspendu
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    /**
     * Vérifier si le compte est en attente d'activation
     */
    public function isPendingActivation()
    {
        return $this->status === 'pending_activation';
    }

    /**
     * Vérifier si c'est un compte d'épargne
     */
    public function isSavingsAccount()
    {
        return $this->account_type === 'savings';
    }

    /**
     * Vérifier si c'est un compte tontine
     */
    public function isTontineAccount()
    {
        return $this->account_type === 'tontine';
    }





}

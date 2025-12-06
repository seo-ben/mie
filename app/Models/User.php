<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasAuditTrail, HasFactory, Notifiable;

    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'role',
        'first_name',
        'last_name',
        'agency_id',
        'is_active',
        'mfa_enabled',
        'mfa_secret',
    ];

    /**
     * Les attributs qui doivent être cachés dans les tableaux ou JSON.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Les attributs qui doivent être castés vers des types spécifiques.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'mfa_enabled' => 'boolean',
    ];

    /**
     * Relation avec l'agence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Relation avec les clients enregistrés par cet utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany(Client::class, 'registered_by');
    }

    /**
     * Scope pour filtrer les utilisateurs actifs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer les utilisateurs par rôle.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, $role)
    {
        return $query->whereIn('role', (array) $role);
    }

    /**
     * Accessor pour obtenir le nom complet.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        $roles = (array) $roles; // Convertir en tableau si un seul rôle est passé
        return in_array($this->role, $roles);
    }

    /**
     * Mutator pour hacher le mot de passe automatiquement (optionnel).
     *
     * @param string $password
     * @return void
     */
    // public function setPasswordAttribute($password)
    // {
    //     $this->attributes['password'] = bcrypt($password);
    // }
}

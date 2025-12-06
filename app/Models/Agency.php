<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'region',
        'phone',
        'manager_id',
        'cash_limit',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cash_limit' => 'decimal:2'
    ];

    // public function manager()
    // {
    //     return $this->belongsTo(User::class, 'manager_id');
    // }

    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }


    public function manager()
    {
        // Utiliser manager_id qui existe dans votre table
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }


}

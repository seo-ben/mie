<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemParameter extends Model
{
    protected $fillable = [
        'parameter_key',
        'parameter_value',
        'parameter_type',
        'description',
        'category',
        'is_editable'
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'parameter_value' => 'json'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

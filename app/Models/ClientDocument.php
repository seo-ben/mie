<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    protected $fillable = [
        'client_id',
        'document_type',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'status'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'verified_at' => 'datetime'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

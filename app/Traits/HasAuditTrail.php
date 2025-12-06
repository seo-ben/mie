<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    /**
     * Boot the trait
     */
    protected static function bootHasAuditTrail()
    {
        static::created(function (Model $model) {
            static::logChange($model, 'CREATED');
        });

        static::updated(function (Model $model) {
            static::logChange($model, 'UPDATED');
        });

        static::deleted(function (Model $model) {
            static::logChange($model, 'DELETED');
        });
    }

    /**
     * Log model changes to audit trail
     */
    protected static function logChange(Model $model, string $action)
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'table_name' => $model->getTable(),
            'record_id' => $model->id,
            'old_values' => $action === 'UPDATED' ? $model->getOriginal() : null,
            'new_values' => $action === 'DELETED' ? null : $model->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Get audit trail for this model
     */
    public function auditTrail()
    {
        return AuditLog::where('table_name', $this->getTable())
            ->where('record_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->with('user');
    }
}
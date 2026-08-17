<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'recipient_type',
        'recipient_id',
        'title',
        'message',
        'type',
        'status',
        'channel',
        'sent_at',
        'read_at',
        'created_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    // Constantes pour les types
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';

    // Constantes pour les statuts
    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';

    // Constantes pour les channels
    const CHANNEL_SMS = 'sms';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_IN_APP = 'in_app';

    // Constantes pour les types de destinataires
    const RECIPIENT_USER = 'user';
    const RECIPIENT_CLIENT = 'client';
    const RECIPIENT_ADMIN = 'admin';

    /**
     * Scope pour les notifications d'un utilisateur admin
     */
    public function scopeForAdmin($query, $userId = null)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('recipient_type', self::RECIPIENT_ADMIN)
              ->orWhere('recipient_type', self::RECIPIENT_USER);
        })->when($userId, function($q) use ($userId) {
            $q->where('recipient_id', $userId);
        });
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')
                     ->where('status', '!=', self::STATUS_READ);
    }

    /**
     * Scope pour les notifications récentes (7 derniers jours)
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour le canal in_app (notifications internes)
     */
    public function scopeInApp($query)
    {
        return $query->where('channel', self::CHANNEL_IN_APP);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead()
    {
        $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now()
        ]);
    }

    /**
     * Marquer comme délivré
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'sent_at' => now()
        ]);
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ || $this->read_at !== null;
    }

    /**
     * Obtenir l'icône selon le type
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SUCCESS => 'fa-check-circle',
            self::TYPE_WARNING => 'fa-exclamation-triangle',
            self::TYPE_ERROR => 'fa-times-circle',
            default => 'fa-info-circle'
        };
    }

    /**
     * Obtenir la classe CSS selon le type
     */
    public function getTypeClassAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SUCCESS => 'text-green-500 bg-green-50',
            self::TYPE_WARNING => 'text-yellow-500 bg-yellow-50',
            self::TYPE_ERROR => 'text-red-500 bg-red-50',
            default => 'text-blue-500 bg-blue-50'
        };
    }

    /**
     * Obtenir le temps relatif
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Relation polymorphique vers la référence
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Créer une notification pour l'admin
     */
    public static function notifyAdmin(
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?int $recipientId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): self {
        return self::create([
            'recipient_type' => self::RECIPIENT_ADMIN,
            'recipient_id' => $recipientId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'channel' => self::CHANNEL_IN_APP,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => now()
        ]);
    }

    /**
     * Créer une notification système (pour tous les admins)
     */
    public static function systemNotification(
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): self {
        return self::create([
            'recipient_type' => self::RECIPIENT_ADMIN,
            'recipient_id' => null, // null = tous les admins
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'channel' => self::CHANNEL_IN_APP,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => now()
        ]);
    }
}

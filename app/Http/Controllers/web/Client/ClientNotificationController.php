<?php
namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClientNotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Liste des notifications du client
     */
    public function index(Request $request)
    {
        $client = auth()->user();

        $notifications = Notification::where('recipient_type', 'client')
            ->where('recipient_id', $client->id)
            ->when($request->get('type'), function($query, $type) {
                $query->where('notification_type', $type);
            })
            ->when($request->get('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->boolean('unread_only'), function($query) {
                $query->whereIn('status', ['pending', 'sent', 'delivered']);
            })
            ->when($request->get('priority'), function($query, $priority) {
                $query->where('priority', $priority);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Grouper par date
        $groupedNotifications = $notifications->getCollection()->groupBy(function($notification) {
            if ($notification->created_at->isToday()) {
                return 'Aujourd\'hui';
            } elseif ($notification->created_at->isYesterday()) {
                return 'Hier';
            } elseif ($notification->created_at->isCurrentWeek()) {
                return 'Cette semaine';
            } else {
                return $notification->created_at->format('F Y');
            }
        });

        return response()->json([
            'success' => true,
            'data' => $notifications->setCollection($groupedNotifications->flatten()),
            'grouped_data' => $groupedNotifications,
            'unread_count' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->whereIn('status', ['pending', 'sent', 'delivered'])
                ->count(),
            'filters' => [
                'types' => $this->getNotificationTypes(),
                'priorities' => ['low', 'normal', 'high', 'urgent']
            ]
        ]);
    }

    /**
     * Détail d'une notification
     */
    public function show($notificationId)
    {
        $client = auth()->user();

        $notification = Notification::where('recipient_type', 'client')
            ->where('recipient_id', $client->id)
            ->findOrFail($notificationId);

        // Marquer comme lue automatiquement lors de la consultation
        if ($notification->status !== 'read') {
            $notification->update([
                'status' => 'read',
                'read_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $notification->id,
                'type' => $notification->notification_type,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'status' => $notification->status,
                'created_at' => $notification->created_at,
                'read_at' => $notification->read_at,
                'reference' => [
                    'type' => $notification->reference_type,
                    'id' => $notification->reference_id
                ],
                'actions' => $this->getNotificationActions($notification)
            ]
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId)
    {
        try {
            $client = auth()->user();

            $notification = Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->findOrFail($notificationId);

            $notification->update([
                'status' => 'read',
                'read_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead()
    {
        try {
            $client = auth()->user();

            $updated = Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->whereIn('status', ['pending', 'sent', 'delivered'])
                ->update([
                    'status' => 'read',
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues',
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy($notificationId)
    {
        try {
            $client = auth()->user();

            $notification = Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->findOrFail($notificationId);

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function clearRead()
    {
        try {
            $client = auth()->user();

            $deleted = Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->where('status', 'read')
                ->where('created_at', '<', now()->subDays(30)) // Garder les 30 derniers jours
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notifications nettoyées',
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paramètres de notification du client
     */
    public function settings()
    {
        $client = auth()->user();

        // Récupérer les préférences depuis la base ou utiliser les valeurs par défaut
        $settings = $client->notification_settings ?? [
            'payment_reminders' => true,
            'transaction_confirmations' => true,
            'loan_updates' => true,
            'promotional_offers' => false,
            'kyc_updates' => true,
            'security_alerts' => true,
            'channels' => [
                'push' => true,
                'sms' => true,
                'email' => false
            ],
            'quiet_hours' => [
                'enabled' => false,
                'start' => '22:00',
                'end' => '08:00'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Mettre à jour les paramètres de notification
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'payment_reminders' => 'boolean',
            'transaction_confirmations' => 'boolean',
            'loan_updates' => 'boolean',
            'promotional_offers' => 'boolean',
            'kyc_updates' => 'boolean',
            'security_alerts' => 'boolean',
            'channels.push' => 'boolean',
            'channels.sms' => 'boolean',
            'channels.email' => 'boolean',
            'quiet_hours.enabled' => 'boolean',
            'quiet_hours.start' => 'nullable|date_format:H:i',
            'quiet_hours.end' => 'nullable|date_format:H:i'
        ]);

        try {
            $client = auth()->user();

            $client->update([
                'notification_settings' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paramètres de notification mis à jour'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des notifications
     */
    public function statistics()
    {
        $client = auth()->user();

        $stats = [
            'total' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->count(),
            'unread' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->whereIn('status', ['pending', 'sent', 'delivered'])
                ->count(),
            'by_type' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->select('notification_type', \DB::raw('COUNT(*) as count'))
                ->groupBy('notification_type')
                ->get()
                ->pluck('count', 'notification_type'),
            'by_priority' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->select('priority', \DB::raw('COUNT(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
            'this_week' => Notification::where('recipient_type', 'client')
                ->where('recipient_id', $client->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Tester l'envoi d'une notification
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:push,sms,email'
        ]);

        try {
            $client = auth()->user();

            $notification = $this->notificationService->createNotification(
                'client',
                $client->id,
                'system_alert',
                'Test de notification',
                'Ceci est une notification de test pour vérifier que vous recevez bien nos alertes.',
                $request->get('channel'),
                ['priority' => 'normal']
            );

            $this->notificationService->send($notification);

            return response()->json([
                'success' => true,
                'message' => 'Notification de test envoyée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getNotificationTypes()
    {
        return [
            'payment_reminder' => 'Rappel de paiement',
            'transaction_confirmation' => 'Confirmation de transaction',
            'loan_offer' => 'Offre de prêt',
            'kyc_update' => 'Mise à jour KYC',
            'system_alert' => 'Alerte système',
            'marketing' => 'Promotions'
        ];
    }

    private function getNotificationActions($notification)
    {
        $actions = [];

        switch ($notification->notification_type) {
            case 'payment_reminder':
                $actions[] = [
                    'label' => 'Payer maintenant',
                    'action' => 'navigate',
                    'target' => '/loans/' . $notification->reference_id . '/payment'
                ];
                break;
            case 'loan_offer':
                $actions[] = [
                    'label' => 'Voir l\'offre',
                    'action' => 'navigate',
                    'target' => '/loans/apply'
                ];
                break;
            case 'kyc_update':
                $actions[] = [
                    'label' => 'Voir mon profil',
                    'action' => 'navigate',
                    'target' => '/profile/kyc'
                ];
                break;
        }

        return $actions;
    }
}

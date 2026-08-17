<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    /**
     * Récupérer les notifications pour l'admin (API JSON)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $notifications = Notification::query()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->where(function($query) use ($user) {
                // Notifications pour tous les admins (recipient_id = null)
                $query->where(function($q) {
                    $q->where('recipient_type', Notification::RECIPIENT_ADMIN)
                      ->whereNull('recipient_id');
                })
                // OU notifications spécifiquement pour cet utilisateur
                ->orWhere(function($q) use ($user) {
                    $q->where('recipient_type', Notification::RECIPIENT_USER)
                      ->where('recipient_id', $user->id);
                })
                ->orWhere(function($q) use ($user) {
                    $q->where('recipient_type', Notification::RECIPIENT_ADMIN)
                      ->where('recipient_id', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unreadCount = Notification::query()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->unread()
            ->where(function($query) use ($user) {
                $query->where(function($q) {
                    $q->where('recipient_type', Notification::RECIPIENT_ADMIN)
                      ->whereNull('recipient_id');
                })
                ->orWhere(function($q) use ($user) {
                    $q->where('recipient_id', $user->id);
                });
            })
            ->count();

        return response()->json([
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'icon' => $notification->icon,
                    'type_class' => $notification->type_class,
                    'time_ago' => $notification->time_ago,
                    'is_read' => $notification->isRead(),
                    'reference_type' => $notification->reference_type,
                    'reference_id' => $notification->reference_id,
                    'created_at' => $notification->created_at->format('d/m/Y H:i')
                ];
            }),
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        Notification::query()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->unread()
            ->where(function($query) use ($user) {
                $query->where(function($q) {
                    $q->where('recipient_type', Notification::RECIPIENT_ADMIN)
                      ->whereNull('recipient_id');
                })
                ->orWhere(function($q) use ($user) {
                    $q->where('recipient_id', $user->id);
                });
            })
            ->update([
                'status' => Notification::STATUS_READ,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    /**
     * Afficher la page de toutes les notifications
     */
    public function all(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::query()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->where(function($query) use ($user) {
                $query->where(function($q) {
                    $q->where('recipient_type', Notification::RECIPIENT_ADMIN)
                      ->whereNull('recipient_id');
                })
                ->orWhere(function($q) use ($user) {
                    $q->where('recipient_id', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    /**
     * Créer une notification de test (pour debug)
     */
    public function createTest(Request $request)
    {
        $types = [
            Notification::TYPE_INFO,
            Notification::TYPE_SUCCESS,
            Notification::TYPE_WARNING,
            Notification::TYPE_ERROR
        ];

        $messages = [
            ['title' => 'Nouveau client inscrit', 'message' => 'Jean Dupont vient de créer un compte.'],
            ['title' => 'Prêt approuvé', 'message' => 'Le prêt #12345 a été approuvé avec succès.'],
            ['title' => 'Paiement en retard', 'message' => 'Le client Marie Martin a un paiement en retard de 3 jours.'],
            ['title' => 'Erreur système', 'message' => 'Une erreur est survenue lors de la synchronisation.'],
            ['title' => 'Tontine terminée', 'message' => 'Le cycle de tontine "Épargne Mensuelle" est terminé.'],
        ];

        $msg = $messages[array_rand($messages)];
        $type = $types[array_rand($types)];

        $notification = Notification::systemNotification(
            $msg['title'],
            $msg['message'],
            $type
        );

        return response()->json([
            'success' => true,
            'notification' => $notification,
            'message' => 'Notification de test créée'
        ]);
    }
}

<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(Notification $notification)
    {
        try {
            $sent = false;
            
            switch ($notification->channel) {
                case 'push':
                    $sent = $this->sendPushNotification($notification);
                    break;
                case 'sms':
                    $sent = $this->sendSMS($notification);
                    break;
                case 'email':
                    $sent = $this->sendEmail($notification);
                    break;
            }

            $notification->update([
                'status' => $sent ? 'sent' : 'failed',
                'sent_at' => $sent ? now() : null
            ]);

            return $sent;

        } catch (\Exception $e) {
            \Log::error('Erreur envoi notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            
            $notification->update(['status' => 'failed']);
            return false;
        }
    }

    private function sendPushNotification(Notification $notification)
    {
        $recipient = $this->getRecipient($notification);
        
        if (!$recipient || !$recipient->fcm_token) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . config('services.fcm.server_key'),
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $recipient->fcm_token,
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->message,
                'icon' => 'ic_notification',
                'sound' => 'default'
            ],
            'data' => [
                'notification_id' => $notification->id,
                'type' => $notification->notification_type,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id
            ]
        ]);

        return $response->successful();
    }

    private function sendSMS(Notification $notification)
    {
        $recipient = $this->getRecipient($notification);
        
        if (!$recipient || !$recipient->phone) {
            return false;
        }

        // Utiliser un service SMS comme Nexmo/Vonage
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.sms.api_key')
        ])->post(config('services.sms.endpoint'), [
            'to' => $recipient->phone,
            'message' => $notification->message,
            'sender' => 'MIE-MICRO'
        ]);

        return $response->successful();
    }

    private function sendEmail(Notification $notification)
    {
        $recipient = $this->getRecipient($notification);
        
        if (!$recipient || !$recipient->email) {
            return false;
        }

        try {
            Mail::send('emails.notification', [
                'title' => $notification->title,
                'message' => $notification->message,
                'recipient' => $recipient
            ], function ($message) use ($notification, $recipient) {
                $message->to($recipient->email, $recipient->full_name)
                        ->subject($notification->title);
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getRecipient(Notification $notification)
    {
        if ($notification->recipient_type === 'client') {
            return Client::find($notification->recipient_id);
        } else {
            return \App\Models\User::find($notification->recipient_id);
        }
    }

    public function createNotification($recipientType, $recipientId, $type, $title, $message, $channel = 'push', $data = [])
    {
        return Notification::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'channel' => $channel,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'priority' => $data['priority'] ?? 'normal'
        ]);
    }

    public function sendPaymentReminder($clientId, $loanId, $dueDate, $amount)
    {
        $message = "Rappel : Votre échéance de prêt de {$amount} FCFA est due le " . $dueDate->format('d/m/Y');
        
        $notification = $this->createNotification(
            'client',
            $clientId,
            'payment_reminder',
            'Échéance de prêt',
            $message,
            'push',
            [
                'reference_type' => 'loan',
                'reference_id' => $loanId,
                'priority' => 'high'
            ]
        );

        return $this->send($notification);
    }
}
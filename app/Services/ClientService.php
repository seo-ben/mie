<?php
namespace App\Services;

use App\Models\Client;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClientService
{
        /**
     * Générer un numéro de client unique
     */
    public function generateClientNumber(): string
    {
        do {
            $number = 'CLT-' . strtoupper(Str::random(3)) . '-' . date('ym') . rand(100, 999);
        } while (Client::where('client_number', $number)->exists());

        return $number;
    }

}

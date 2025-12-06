<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientValidationApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Récupère la liste des clients en attente de validation.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $pendingClients = Client::with('user')
                ->where('registration_type', 'self')
                ->where('registration_status', 'pending')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $pendingClients,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pending client registrations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch pending registrations',
            ], 500);
        }
    }

    /**
     * Valide ou rejette l’enregistrement d’un client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Client  $client
     * @return JsonResponse
     */
    public function update(
        \Illuminate\Http\Request $request,
        Client $client
    ): JsonResponse {
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Validation manuelle (équivalent du FormRequest)
        $validated = $request->validate([
            'status'            => 'required|in:approved,rejected',
            'rejection_reason'  => 'required_if:status,rejected',
            'agency_id'         => 'required_if:status,approved|exists:agencies,id',
        ]);

        try {
            $client->registration_status = $validated['status'];
            $client->rejection_reason = $validated['rejection_reason'] ?? null;
            $client->validated_by = auth()->id();
            $client->validated_at = now();

            if ($validated['status'] === 'approved') {
                $client->agency_id = $validated['agency_id'];
                // Active le compte utilisateur et les comptes du client
                $client->user->is_active = true;
                $client->user->save();

                $client->accounts()->update(['status' => 'active']);
            }

            $client->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration ' . $validated['status'],
                'data'    => $client->fresh(['user', 'accounts', 'agency']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error validating client registration', [
                'client_id' => $client->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process registration validation',
            ], 500);
        }
    }
}

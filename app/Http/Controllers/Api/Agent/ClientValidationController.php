<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
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
     * Valide ou rejette l'enregistrement d'un client.
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'status'           => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'agency_id'        => 'required_if:status,approved|exists:agencies,id',
        ]);

        try {
            $client->registration_status = $validated['status'];
            $client->rejection_reason    = $validated['rejection_reason'] ?? null;
            $client->validated_by        = auth()->id();
            $client->validated_at        = now();

            if ($validated['status'] === 'approved') {
                $client->agency_id = $validated['agency_id'];

                // CORRECTION : vérification que la relation user existe avant d'y accéder
                if ($client->user) {
                    $client->user->is_active = true;
                    $client->user->save();
                } else {
                    Log::warning('Client approuvé sans utilisateur lié', [
                        'client_id' => $client->id,
                    ]);
                }

                $client->accounts()->update(['status' => 'active']);
            }

            $client->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration ' . $validated['status'],
                // CORRECTION : agency inclus dans le fresh() pour complétude (cohérent avec le Web)
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
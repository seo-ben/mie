<?php

namespace App\Http\Controllers\web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientValidationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getPendingRegistrations(): JsonResponse
    {
        try {
            $pendingClients = Client::with('user')
                ->where('registration_type', 'self')
                ->where('registration_status', 'pending')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pendingClients
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pending registrations', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch pending registrations'
            ], 500);
        }
    }

    public function validateRegistration(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'required_if:status,rejected',
                'agency_id' => 'required_if:status,approved|exists:agencies,id'
            ]);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $client->registration_status = $validated['status'];
            $client->rejection_reason = $validated['rejection_reason'] ?? null;
            $client->validated_by = auth()->id();
            $client->validated_at = now();

            if ($validated['status'] === 'approved') {
                $client->agency_id = $validated['agency_id'];
                $client->user->is_active = true;
                $client->user->save();
                $client->accounts()->update(['status' => 'active']);
            }

            $client->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration ' . $validated['status'],
                'data' => $client->fresh(['user', 'accounts'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error validating registration', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process registration validation'
            ], 500);
        }
    }
}

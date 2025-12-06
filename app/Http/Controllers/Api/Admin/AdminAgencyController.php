<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\Request;

class AdminAgencyController extends Controller
{
    /**
     * Liste de toutes les agences
     */
    public function index(Request $request)
    {
        $agencies = Agency::with(['manager', 'users'])
            ->when($request->get('search'), function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
            })
            ->when($request->get('region'), function($query, $region) {
                $query->where('region', $region);
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $agencies
        ]);
    }

    /**
     * Créer une nouvelle agence
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:agencies,code',
            'city' => 'required|string|max:100',
            'region' => 'required|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $agency = Agency::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'city' => $validated['city'],
                'region' => $validated['region'],
                'manager_id' => $validated['manager_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agence créée avec succès',
                'data' => $agency
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’agence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une agence spécifique
     */
    public function show($agencyId)
    {
        $agency = Agency::with(['manager', 'users'])->findOrFail($agencyId);

        return response()->json([
            'success' => true,
            'data' => $agency
        ]);
    }

    /**
     * Mettre à jour une agence
     */
    public function update(Request $request, $agencyId)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:agencies,code,' . $agencyId,
            'city' => 'sometimes|string|max:100',
            'region' => 'sometimes|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $agency = Agency::findOrFail($agencyId);
            $agency->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Agence mise à jour avec succès',
                'data' => $agency
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
     * Supprimer une agence (désactiver)
     */
    public function destroy($agencyId)
    {
        try {
            $agency = Agency::findOrFail($agencyId);

            if ($agency->clients()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une agence avec des clients actifs'
                ], 422);
            }

            $agency->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Agence désactivée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

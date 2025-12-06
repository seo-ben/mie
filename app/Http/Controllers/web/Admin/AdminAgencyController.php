<?php

namespace App\Http\Controllers\web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAgencyController extends Controller
{
    /**
     * Liste des agences
     */
    public function index(Request $request)
    {
        $agencies = Agency::with(['manager', 'users'])
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->region, fn($q, $r) => $q->where('region', $r))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', request()->boolean('is_active')))
            ->paginate($request->get('per_page', 15));

        return view('admin.agencies.index', compact('agencies'));
    }

    /**
     * Récupérer les utilisateurs d'une agence
     */
    public function getAgencyUsers($agencyId)
    {
        try {
            $users = User::where('agency_id', $agencyId)
                        ->where('is_active', true)
                        ->orderBy('first_name')
                        ->get(['id', 'first_name', 'last_name', 'email', 'role']);

            return response()->json(['success' => true, 'data' => $users]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.agencies.create');
    }


    /**
     * Afficher une agence
     */
    public function show($agencyId)
    {
        $agency = Agency::with(['manager', 'users'])->findOrFail($agencyId);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'data' => $agency]);
        }

        return view('admin.agencies.show', compact('agency'));
    }

    /**
     * Formulaire édition
     */
    public function edit($agencyId)
    {
        $agency = Agency::with(['manager'])->findOrFail($agencyId);

        return view('admin.agencies.edit', compact('agency'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:agencies,code',
            'city'       => 'required|string|max:100',
            'region'     => 'required|string|max:100',
            'manager_id' => 'nullable|exists:users,id', // ✅ Ajouter
            'is_active'  => 'nullable|boolean',
        ]);

        try {
            $agency = Agency::create([
                'name'       => $validated['name'],
                'code'       => $validated['code'],
                'city'       => $validated['city'],
                'region'     => $validated['region'],
                'manager_id' => $validated['manager_id'] ?? null, // ✅ Ajouter
                'is_active'  => $validated['is_active'] ?? true,
            ]);

            return redirect()
                ->route('admin.agencies.index')
                ->with('success', 'Agence créée avec succès.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $agencyId)
    {
        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'code'       => 'sometimes|string|max:50|unique:agencies,code,' . $agencyId,
            'city'       => 'sometimes|string|max:100',
            'region'     => 'sometimes|string|max:100',
            'manager_id' => 'nullable|exists:users,id', // ✅ Ajouter
            'is_active'  => 'nullable|boolean',
        ]);

        try {
            $agency = Agency::findOrFail($agencyId);
            $agency->update($validated);

            return redirect()
                ->route('admin.agencies.index')
                ->with('success', 'Agence mise à jour avec succès.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Désactiver une agence
     */
    public function destroy($agencyId)
    {
        try {
            $agency = Agency::findOrFail($agencyId);

            if ($agency->clients()->count() > 0) {
                return back()->with('error', 'Impossible de supprimer une agence avec des clients actifs.');
            }

            $agency->update(['is_active' => false]);

            return redirect()
                ->route('admin.agencies.index')
                ->with('success', 'Agence désactivée avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

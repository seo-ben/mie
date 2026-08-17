<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use App\Services\ConfigurationService;
use App\Http\Requests\UpdateParametersRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminConfigController extends Controller
{
    public function __construct(
        private ConfigurationService $configService
    ) {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole(['administrateur_systeme', 'administrateur_reglementaire'])) {
                abort(403, 'Accès non autorisé');
            }
            return $next($request);
        });
    }

    /**
     * Afficher la page des paramètres système
     */
    public function parameters(Request $request)
    {
        $this->ensureDefaultFeesExist();

        $category = $request->get('category');
        $search = $request->get('search');

        $parameters = SystemParameter::when($category, function($query, $category) {
                $query->where('category', $category);
            })
            ->when($search, function($query, $search) {
                $query->where('parameter_key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('category')
            ->orderBy('parameter_key')
            ->get()
            ->groupBy('category');

        $categories = $this->getParameterCategories();

        return view('admin.config.parameters', compact('parameters', 'categories', 'category', 'search'));
    }

    /**
     * Mettre à jour les paramètres système
     */
    public function updateParameters(UpdateParametersRequest $request)
    {
        try {
            $updated = $this->configService->updateParameters($request->validated()['parameters']);

            // Log de l'action
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE_SYSTEM_PARAMETERS',
                'entity_type' => 'system_config',
                'entity_id' => 0,
                'additional_data' => [
                    'updated_parameters' => array_keys($updated),
                    'user_ip' => request()->ip()
                ]
            ]);

            // Invalider tout le cache
            Cache::flush();

            return redirect()
                ->route('admin.config.parameters', ['_t' => time()])
                ->with('success', 'Paramètres mis à jour avec succès')
                ->with('updated_count', count($updated));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }


    /**
     * Afficher la page de configuration des intégrations externes
     */
    public function integrations()
    {
        $integrations = Cache::remember('integrations_status', 300, function () {
            return $this->configService->getIntegrationStatus();
        });

        $availableServices = [
            'mtn' => [
                'name' => 'MTN Mobile Money',
                'icon' => 'mtn-icon.png',
                'category' => 'mobile_money'
            ],
            'orange' => [
                'name' => 'Orange Money',
                'icon' => 'orange-icon.png',
                'category' => 'mobile_money'
            ],
            'moov' => [
                'name' => 'Moov Money',
                'icon' => 'moov-icon.png',
                'category' => 'mobile_money'
            ],
            'email' => [
                'name' => 'Service Email',
                'icon' => 'email-icon.png',
                'category' => 'notifications'
            ],
            'sms' => [
                'name' => 'Service SMS',
                'icon' => 'sms-icon.png',
                'category' => 'notifications'
            ],
        ];

        return view('admin.config.integrations', compact('integrations', 'availableServices'));
    }

    /**
     * Mettre à jour les configurations d'intégration
     */
    public function updateIntegrations(Request $request)
    {
        $request->validate([
            'service' => 'required|string|in:mtn,orange,moov,email,sms',
            'enabled' => 'required|boolean',
            'config' => 'required|array',
        ]);

        try {
            $result = $this->configService->updateIntegrations($request->all());

            // Invalider le cache
            Cache::forget('integrations_status');

            if ($result['success']) {
                return redirect()
                    ->route('admin.config.integrations')
                    ->with('success', $result['message']);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('warning', $result['message']);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour des intégrations : ' . $e->getMessage());
        }
    }

    /**
     * Test de connectivité des intégrations (AJAX)
     */
    public function testIntegration(Request $request)
    {
        $request->validate([
            'service' => 'required|string|in:mtn,orange,moov,email,sms'
        ]);

        try {
            $service = $request->get('service');
            $result = $this->configService->testIntegration($service);

            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'Test effectué',
                'data' => $result['data'] ?? null,
                'response_time' => $result['response_time'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher la page de gestion des sauvegardes
     */
    public function backups()
    {
        $backupsList = $this->configService->listBackups();
        // dd($backupsList);
        return view('admin.config.backups', compact('backupsList'));
    }

    /**
     * Créer une sauvegarde de configuration
     */
    public function createBackup(Request $request)
    {
        $request->validate([
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $backupPath = $this->configService->createConfigBackup($request->description);

            // Log de l'action
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'CREATE_CONFIG_BACKUP',
                'entity_type' => 'system_config',
                'entity_id' => 0,
                'additional_data' => [
                    'file' => $backupPath,
                    'description' => $request->description,
                    'user_ip' => request()->ip()
                ]
            ]);

            return redirect()
                ->route('admin.config.backups')
                ->with('success', 'Sauvegarde créée avec succès')
                ->with('file', basename($backupPath));

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Restaurer une sauvegarde de configuration
     */
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);

        try {
            $result = $this->configService->restoreConfigBackup($request->file);

            if ($result['success']) {
                // Log de l'action
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'RESTORE_CONFIG_BACKUP',
                    'entity_type' => 'system_config',
                    'entity_id' => 0,
                    'additional_data' => [
                        'file' => $request->file,
                        'restored_parameters' => $result['restored_count'] ?? 0,
                        'user_ip' => request()->ip()
                    ]
                ]);

                // Invalider tous les caches
                Cache::flush();

                return redirect()
                    ->route('admin.config.parameters')
                    ->with('success', $result['message']);
            }

            return redirect()
                ->back()
                ->with('error', $result['message']);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la restauration : ' . $e->getMessage());
        }
    }

    /**
     * Télécharger un fichier de sauvegarde
     */
    public function downloadBackup($file)
    {
        try {
            $filePath = $this->configService->getBackupPath($file);

            if (!file_exists($filePath)) {
                return redirect()
                    ->back()
                    ->with('error', 'Fichier de sauvegarde introuvable');
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors du téléchargement : ' . $e->getMessage());
        }
    }


    /**
     * Supprimer une sauvegarde
     */
    public function deleteBackup(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);

        try {
            $result = $this->configService->deleteBackup($request->file);

            if ($result['success']) {
                return redirect()
                    ->route('admin.config.backups')
                    ->with('success', 'Sauvegarde supprimée avec succès');
            }

            return redirect()
                ->back()
                ->with('error', $result['message']);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Afficher les logs de configuration
     */
    public function logs(Request $request)
    {
        $logs = \App\Models\AuditLog::where('action', 'UPDATE_SYSTEM_PARAMETERS')
            ->with('user:id,first_name,last_name,email')
            ->when($request->get('action'), function($query, $action) {
                $query->where('action', $action);
            })
            ->when($request->get('user_id'), function($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        $actions = [
            'UPDATE_SYSTEM_PARAMETERS' => 'Mise à jour des paramètres',
            'CREATE_CONFIG_BACKUP' => 'Création de sauvegarde',
            'RESTORE_CONFIG_BACKUP' => 'Restauration de sauvegarde',
        ];

        return view('admin.config.logs', compact('logs', 'actions'));
    }

    /**
     * Réinitialiser les paramètres par défaut
     */
    public function resetDefaults(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string',
            'confirm' => 'required|accepted'
        ]);

        try {
            $category = $request->get('category');
            $result = $this->configService->resetToDefaults($category);

            // Log de l'action
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'RESET_CONFIG_DEFAULTS',
                'entity_type' => 'system_config',
                'entity_id' => 0,
                'additional_data' => [
                    'category' => $category ?? 'all',
                    'reset_count' => $result['count'] ?? 0,
                    'user_ip' => request()->ip()
                ]
            ]);

            // Invalider le cache (sans tags)
            Cache::flush();

            return redirect()
                ->route('admin.config.parameters')
                ->with('success', $result['message'])
                ->with('reset_count', $result['count']);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les catégories de paramètres
     */
    private function getParameterCategories(): array
    {
        // Récupérer les catégories existantes en base de données
        $dbCategories = SystemParameter::select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        // Labels prédéfinis pour un affichage propre
        $labels = [
            'fees' => 'Frais et Tarifs',
            'rates' => 'Taux d\'Intérêt',
            'limits' => 'Limites et Seuils',
            'integrations' => 'Intégrations',
            'security' => 'Sécurité',
            'notifications' => 'Notifications',
            'loans' => 'Prêts et Crédits',
            'accounts' => 'Comptes',
            'tontine' => 'Tontines',
            'savings' => 'Épargne',
            'general' => 'Général',
            'system' => 'Système'
        ];

        $categories = [];
        foreach ($dbCategories as $cat) {
            $categories[$cat] = $labels[$cat] ?? ucfirst($cat);
        }

        // Si aucune catégorie en base, on garde les labels par défaut pour éviter une liste vide
        if (empty($categories)) {
            return $labels;
        }

        return $categories;
    }
    /**
     * S'assurer que les paramètres de frais de retrait existent
     */
    private function ensureDefaultFeesExist()
    {
        $defaults = [
            'savings_withdrawal_fee_percentage' => [
                'value' => '2.0',
                'description' => 'Pourcentage retrait Épargne',
                'category' => 'fees'
            ],
            'savings_withdrawal_fee_fixed' => [
                'value' => '0',
                'description' => 'Frais fixe retrait Épargne',
                'category' => 'fees'
            ],
            'tontine_withdrawal_fee_percentage' => [
                'value' => '3.0',
                'description' => 'Pourcentage retrait Tontine',
                'category' => 'fees'
            ],
            'tontine_withdrawal_fee_fixed' => [
                'value' => '0',
                'description' => 'Frais fixe retrait Tontine',
                'category' => 'fees'
            ]
        ];

        foreach ($defaults as $key => $data) {
            SystemParameter::firstOrCreate(
                ['parameter_key' => $key],
                [
                    'parameter_value' => $data['value'],
                    'parameter_type' => 'number',
                    'description' => $data['description'],
                    'category' => $data['category'],
                    'created_by' => auth()->id() ?? 1,
                    'is_editable' => true
                ]
            );
        }
    }
}

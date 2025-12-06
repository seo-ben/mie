<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use App\Services\ConfigurationService;
use App\Http\Requests\UpdateParametersRequest;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function __construct(
        private ConfigurationService $configService
    ) {}

    /**
     * Récupérer tous les paramètres système
     */
    public function parameters(Request $request)
    {
        $parameters = SystemParameter::when($request->get('category'), function($query, $category) {
            $query->where('category', $category);
        })
        ->when($request->get('search'), function($query, $search) {
            $query->where('parameter_key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        })
        ->orderBy('category')
        ->orderBy('parameter_key')
        ->get()
        ->groupBy('category');

        return response()->json([
            'success' => true,
            'data' => $parameters,
            'categories' => $this->getParameterCategories()
        ]);
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

            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'data' => $updated
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
     * Configuration des intégrations externes
     */
    public function integrations()
    {
        $integrations = $this->configService->getIntegrationStatus();

        return response()->json([
            'success' => true,
            'data' => $integrations
        ]);
    }

    /**
     * Mettre à jour les configurations d'intégration
     */
    public function updateIntegrations(Request $request)
    {
        try {
            $result = $this->configService->updateIntegrations($request->all());

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des intégrations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de connectivité des intégrations
     */
    public function testIntegrations(Request $request)
    {
        $service = $request->get('service'); // mtn, orange, moov, email, sms
        $result = $this->configService->testIntegration($service);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Sauvegarde/Restauration de configuration
     */
    public function backup()
    {
        try {
            $backupPath = $this->configService->createConfigBackup();

            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'backup_file' => $backupPath
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getParameterCategories()
    {
        return [
            'fees' => 'Frais et Tarifs',
            'rates' => 'Taux d\'Intérêt',
            'limits' => 'Limites et Seuils',
            'integrations' => 'Intégrations',
            'security' => 'Sécurité',
            'notifications' => 'Notifications',
            'loans' => 'Prêts et Crédits',
            'accounts' => 'Comptes'
        ];
    }
}

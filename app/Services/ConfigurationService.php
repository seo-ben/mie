<?php

namespace App\Services;

use App\Models\SystemParameter;
use App\Models\IntegrationConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ConfigurationService
{
    /**
     * Mettre à jour les paramètres système
     */
    public function updateParameters(array $parameters): array
    {
        \Log::info('Updating parameters', ['parameters' => $parameters]);
        $updated = [];
        foreach ($parameters as $id => $value) {
            $param = SystemParameter::find($id);
            if ($param) {
                $param->parameter_value = $value;
                $param->save();
                $updated[$param->parameter_key] = $value;
                \Log::info('Updated parameter', ['key' => $param->parameter_key, 'value' => $value]);
            }
        }
        return $updated;
    }

    /**
     * Valider la valeur d'un paramètre selon son type
     */
    private function validateParameterValue($value, string $dataType)
    {
        return match($dataType) {
            'integer' => filter_var($value, FILTER_VALIDATE_INT),
            'decimal' => filter_var($value, FILTER_VALIDATE_FLOAT),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => is_string($value) ? $value : (string) $value,
            'json' => $this->validateJson($value),
            'date' => $this->validateDate($value),
            'percentage' => $this->validatePercentage($value),
            default => $value
        };
    }

    /**
     * Valider une chaîne JSON
     */
    private function validateJson($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $value;
    }

    /**
     * Valider une date
     */
    private function validateDate($value): string|false
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Valider un pourcentage
     */
    private function validatePercentage($value): float|false
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($float === false || $float < 0 || $float > 100) {
            return false;
        }

        return $float;
    }

    /**
     * Valider les contraintes personnalisées
     */
    private function validateConstraints($value, ?string $rules): void
    {
        if (!$rules) {
            return;
        }

        $constraints = json_decode($rules, true);

        if (!$constraints) {
            return;
        }

        // Min/Max pour les nombres
        if (isset($constraints['min']) && $value < $constraints['min']) {
            throw new \Exception("La valeur doit être supérieure ou égale à {$constraints['min']}");
        }

        if (isset($constraints['max']) && $value > $constraints['max']) {
            throw new \Exception("La valeur doit être inférieure ou égale à {$constraints['max']}");
        }

        // Min/Max length pour les chaînes
        if (isset($constraints['min_length']) && strlen($value) < $constraints['min_length']) {
            throw new \Exception("La longueur doit être au moins {$constraints['min_length']} caractères");
        }

        if (isset($constraints['max_length']) && strlen($value) > $constraints['max_length']) {
            throw new \Exception("La longueur ne doit pas dépasser {$constraints['max_length']} caractères");
        }

        // Valeurs autorisées
        if (isset($constraints['allowed_values']) && !in_array($value, $constraints['allowed_values'])) {
            throw new \Exception("Valeur non autorisée");
        }

        // Pattern regex
        if (isset($constraints['pattern']) && !preg_match($constraints['pattern'], $value)) {
            throw new \Exception("Format invalide");
        }
    }

    /**
     * Obtenir le statut des intégrations
     */
    public function getIntegrationStatus(): array
    {
        $integrations = IntegrationConfig::all();
        $status = [];

        foreach ($integrations as $integration) {
            $status[$integration->service_name] = [
                'enabled' => $integration->is_enabled,
                'status' => $integration->status,
                'last_check' => $integration->last_check_at?->format('d/m/Y H:i'),
                'config' => json_decode($integration->config_data, true),
                'error_message' => $integration->error_message,
            ];
        }

        return $status;
    }

    /**
     * Mettre à jour les configurations d'intégration
     */
    public function updateIntegrations(array $data): array
    {
        DB::beginTransaction();
        try {
            $integration = IntegrationConfig::firstOrNew([
                'service_name' => $data['service']
            ]);

            // Crypter les informations sensibles
            $config = $this->encryptSensitiveData($data['config']);

            $integration->fill([
                'is_enabled' => $data['enabled'],
                'config_data' => json_encode($config),
                'updated_by' => auth()->id(),
            ]);

            // Tester la connexion si activé
            if ($data['enabled']) {
                $testResult = $this->testIntegration($data['service'], $config);

                if (!$testResult['success']) {
                    $integration->status = 'error';
                    $integration->error_message = $testResult['message'];
                    $integration->save();

                    DB::commit();

                    return [
                        'success' => false,
                        'message' => 'Configuration sauvegardée mais le test de connexion a échoué : ' . $testResult['message']
                    ];
                }

                $integration->status = 'active';
                $integration->error_message = null;
                $integration->last_check_at = now();
            } else {
                $integration->status = 'disabled';
            }

            $integration->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Configuration mise à jour avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crypter les données sensibles
     */
    private function encryptSensitiveData(array $config): array
    {
        $sensitiveKeys = ['api_key', 'secret_key', 'password', 'token', 'private_key'];

        foreach ($config as $key => $value) {
            if (in_array($key, $sensitiveKeys) && !empty($value)) {
                $config[$key] = encrypt($value);
            }
        }

        return $config;
    }

    /**
     * Tester une intégration
     */
    public function testIntegration(string $service, ?array $config = null): array
    {
        $startTime = microtime(true);

        try {
            if (!$config) {
                $integration = IntegrationConfig::where('service_name', $service)->first();

                if (!$integration) {
                    return [
                        'success' => false,
                        'message' => 'Intégration non configurée'
                    ];
                }

                $config = json_decode($integration->config_data, true);
            }

            $result = match($service) {
                'mtn' => $this->testMtnMobileMoney($config),
                'orange' => $this->testOrangeMoney($config),
                'moov' => $this->testMoovMoney($config),
                'email' => $this->testEmailService($config),
                'sms' => $this->testSmsService($config),
                default => ['success' => false, 'message' => 'Service non supporté']
            };

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $result['response_time'] = $responseTime . ' ms';

            // Mettre à jour le statut
            if (isset($integration)) {
                $integration->update([
                    'last_check_at' => now(),
                    'status' => $result['success'] ? 'active' : 'error',
                    'error_message' => $result['success'] ? null : $result['message']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response_time' => round((microtime(true) - $startTime) * 1000, 2) . ' ms'
            ];
        }
    }

    /**
     * Tester MTN Mobile Money
     */
    private function testMtnMobileMoney(array $config): array
    {
        try {
            $apiKey = decrypt($config['api_key'] ?? '');
            $apiUrl = $config['api_url'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'X-Target-Environment' => $config['environment'] ?? 'sandbox'
                ])
                ->get($apiUrl . '/v1_0/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'data' => ['balance' => $response->json()['availableBalance'] ?? null]
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur de connexion : ' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester Orange Money
     */
    private function testOrangeMoney(array $config): array
    {
        try {
            $apiKey = decrypt($config['api_key'] ?? '');
            $apiUrl = $config['api_url'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey
                ])
                ->get($apiUrl . '/orange-money-webpay/dev/v1/webpayment');

            if ($response->successful() || $response->status() === 401) {
                return [
                    'success' => true,
                    'message' => 'API accessible'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur de connexion'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester Moov Money
     */
    private function testMoovMoney(array $config): array
    {
        try {
            $apiUrl = $config['api_url'] ?? '';
            $username = $config['username'] ?? '';
            $password = decrypt($config['password'] ?? '');

            $response = Http::timeout(10)
                ->withBasicAuth($username, $password)
                ->get($apiUrl . '/api/status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur de connexion'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester le service email
     */
    private function testEmailService(array $config): array
    {
        try {
            // Test simple de configuration SMTP
            $transport = (new \Swift_SmtpTransport(
                $config['smtp_host'] ?? '',
                $config['smtp_port'] ?? 587
            ))
                ->setUsername($config['smtp_username'] ?? '')
                ->setPassword(decrypt($config['smtp_password'] ?? ''));

            $transport->start();
            $transport->stop();

            return [
                'success' => true,
                'message' => 'Configuration SMTP valide'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur SMTP : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester le service SMS
     */
    private function testSmsService(array $config): array
    {
        try {
            $apiKey = decrypt($config['api_key'] ?? '');
            $apiUrl = $config['api_url'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey
                ])
                ->get($apiUrl . '/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'data' => ['balance' => $response->json()['balance'] ?? null]
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur de connexion'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lister les sauvegardes
     */
    public function listBackups(): array
    {
        $backupPath = storage_path('app/backups/config');

        if (!file_exists($backupPath)) {
            return [];
        }

        $files = glob($backupPath . '/*.json');
        $backups = [];

        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file), true);

            $backups[] = [
                'filename' => basename($file),
                'created_at' => $content['created_at'] ?? null,
                'created_by' => $content['created_by'] ?? null,
                'description' => $content['description'] ?? null,
                'parameters_count' => count($content['parameters'] ?? []),
                'size' => $this->formatBytes(filesize($file)),
            ];
        }

        // Trier par date décroissante
        usort($backups, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });

        return $backups;
    }

    /**
     * Créer une sauvegarde de configuration
     */
    public function createConfigBackup(?string $description = null): string
    {
        $backupPath = storage_path('app/backups/config');

        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $parameters = SystemParameter::all()->map(function($param) {
            return [
                'parameter_key' => $param->parameter_key,
                'parameter_value' => $param->parameter_value,
                'category' => $param->category,
                'data_type' => $param->data_type,
                'description' => $param->description,
                'validation_rules' => $param->validation_rules,
                'is_editable' => $param->is_editable,
            ];
        })->toArray();

        $backup = [
            'created_at' => now()->toIso8601String(),
            'created_by' => auth()->user()->full_name ?? 'System',
            'description' => $description,
            'version' => '1.0',
            'parameters' => $parameters,
        ];

        $filename = 'config_backup_' . now()->format('Y-m-d_His') . '.json';
        $filePath = $backupPath . '/' . $filename;

        file_put_contents($filePath, json_encode($backup, JSON_PRETTY_PRINT));

        return $filePath;
    }

    /**
     * Restaurer une sauvegarde de configuration
     */
    public function restoreConfigBackup(string $filename): array
    {
        $filePath = $this->getBackupPath($filename);

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Fichier de sauvegarde introuvable'
            ];
        }

        $backup = json_decode(file_get_contents($filePath), true);

        if (!$backup || !isset($backup['parameters'])) {
            return [
                'success' => false,
                'message' => 'Format de sauvegarde invalide'
            ];
        }

        DB::beginTransaction();
        try {
            $restored = 0;

            foreach ($backup['parameters'] as $paramData) {
                $parameter = SystemParameter::where('parameter_key', $paramData['parameter_key'])->first();

                if ($parameter) {
                    $parameter->update([
                        'parameter_value' => $paramData['parameter_value'],
                        'updated_by' => auth()->id(),
                    ]);
                    $restored++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Sauvegarde restaurée avec succès ({$restored} paramètres)",
                'restored_count' => $restored
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Erreur lors de la restauration : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le chemin d'une sauvegarde
     */
    public function getBackupPath(string $filename): string
    {
        return storage_path('app/backups/config/' . basename($filename));
    }

    /**
     * Supprimer une sauvegarde
     */
    public function deleteBackup(string $filename): array
    {
        $filePath = $this->getBackupPath($filename);

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Fichier introuvable'
            ];
        }

        if (unlink($filePath)) {
            return [
                'success' => true,
                'message' => 'Sauvegarde supprimée avec succès'
            ];
        }

        return [
            'success' => false,
            'message' => 'Erreur lors de la suppression'
        ];
    }

    /**
     * Réinitialiser les paramètres par défaut
     */
    public function resetToDefaults(?string $category = null): array
    {
        DB::beginTransaction();
        try {
            $query = SystemParameter::where('is_editable', true);

            if ($category) {
                $query->where('category', $category);
            }

            $count = $query->count();

            $query->update([
                'parameter_value' => DB::raw('default_value'),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            $message = $category
                ? "Paramètres de la catégorie '{$category}' réinitialisés ({$count})"
                : "Tous les paramètres réinitialisés ({$count})";

            return [
                'success' => true,
                'message' => $message,
                'count' => $count
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Formater la taille d'un fichier
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' octets';
        }
    }
}

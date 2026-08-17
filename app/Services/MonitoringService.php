<?php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    // public function getSystemHealth()
    // {
    //     return [
    //         'database' => $this->checkDatabaseHealth(),
    //         'storage' => $this->checkStorageHealth(),
    //         'api_performance' => $this->getAPIPerformance(),
    //         'error_rate' => $this->getErrorRate(),
    //         'security_alerts' => $this->getSecurityAlerts()
    //     ];
    // }

    public function checkDatabaseHealth()
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    public function checkStorageHealth()
    {
        try {
            $path = storage_path();
            $writable = is_writable($path);

            return [
                'status' => $writable ? 'healthy' : 'unhealthy',
                'path' => $path
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getAPIPerformance(): array
    {
        $logs = AuditLog::where('created_at', '>', now()->subDay())
            ->where('action', 'like', '%api%')
            ->get();

        $responseTimes = [];

        foreach ($logs as $log) {
            $endpoint = $log->action;
            $extra = json_decode($log->additional_data, true);
            $time = $extra['response_time_ms'] ?? 0;

            if (!isset($responseTimes[$endpoint])) {
                $responseTimes[$endpoint] = [];
            }
            $responseTimes[$endpoint][] = $time;
        }

        // Moyenne par endpoint
        $avgResponseTimes = [];
        foreach ($responseTimes as $endpoint => $times) {
            $avgResponseTimes[$endpoint] = count($times) ? round(array_sum($times) / count($times), 2) : 0;
        }

        return [
            'average_response_time' => count($logs) ? round($logs->avg(fn($l) => json_decode($l->additional_data, true)['response_time_ms'] ?? 0), 2) : 0,
            'response_times' => $avgResponseTimes, // <-- maintenant cette clé existe
        ];
    }

    public function getErrorRate()
    {
        $totalRequests = AuditLog::where('created_at', '>', Carbon::now()->subHour())->count();
        $errorRequests = AuditLog::where('created_at', '>', Carbon::now()->subHour())
            ->whereJsonContains('additional_data->response_status', ['>=', 400])
            ->count();

        return [
            'total_requests' => $totalRequests,
            'error_requests' => $errorRequests,
            'error_rate_percent' => $totalRequests > 0 ? round(($errorRequests / $totalRequests) * 100, 2) : 0
        ];
    }

    public function logPerformanceMetric($endpoint, $responseTime, $statusCode, $userId = null)
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'API_CALL',
            // 'entity_type' => 'performance',
            'entity_id' => 0,
            'additional_data' => [
                'endpoint' => $endpoint,
                'response_time_ms' => $responseTime,
                'status_code' => $statusCode
            ]
        ]);
    }
    public function getFraudAlerts()
    {
        try {
            // On recherche les entrées liées à une fraude dans les logs
            $query = \App\Models\AuditLog::query()
                ->where(function ($q) {
                    // 1️⃣ Chercher les actions contenant "fraud"
                    $q->where('action', 'like', '%fraud%')
                    // 2️⃣ OU les données JSON contenant un type d’alerte "fraud"
                    ->orWhereRaw("JSON_EXTRACT(additional_data, '$.alert_type') = 'fraud'")
                    // 3️⃣ OU des anomalies de transaction ou paiement
                    ->orWhere('table_name', 'like', '%transaction%');
                })
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            // On transforme le résultat pour l’affichage dans la vue
            return $query->map(function ($log) {
                $additional = json_decode($log->additional_data, true);

                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'table_name' => $log->table_name,
                    'record_id' => $log->record_id,
                    'ip_address' => $log->ip_address,
                    'risk_level' => $additional['risk_level'] ?? 'medium',
                    'description' => $additional['description'] ?? 'Suspicious activity detected',
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Erreur getFraudAlerts: ' . $e->getMessage());
            return [];
        }
    }
    public function getSuspiciousLogins()
    {
        try {
            // 🔍 Recherche méticuleuse des connexions suspectes dans les logs
            $query = \App\Models\AuditLog::query()
                ->where(function ($q) {
                    // On cherche les actions liées aux connexions
                    $q->where('action', 'like', '%login%')
                    ->orWhere('action', 'like', '%auth%');
                })
                // 🔥 Et on filtre celles dont les données JSON contiennent des signes suspects
                ->where(function ($q) {
                    $q->whereRaw("JSON_EXTRACT(additional_data, '$.suspicious') = true")
                    ->orWhereRaw("JSON_EXTRACT(additional_data, '$.failed_attempts') >= 3")
                    ->orWhereRaw("JSON_EXTRACT(additional_data, '$.location_anomaly') = true");
                })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            // ✨ Transformation élégante du résultat pour ta vue
            return $query->map(function ($log) {
                $extra = json_decode($log->additional_data, true);

                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'priority' => $extra['priority'] ?? 'medium',
                    'reason' => $extra['reason'] ?? 'Connexion inhabituelle détectée',
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans getSuspiciousLogins : ' . $e->getMessage());
            return [];
        }
    }
    public function countTotalAlerts()
    {
        $securityAlerts = $this->getSecurityAlerts();
        $fraudAlerts = $this->getFraudAlerts();
        $loginAlerts = $this->getSuspiciousLogins();

        return count($securityAlerts) + count($fraudAlerts) + count($loginAlerts);
    }
    public function getActiveUsersStats()
    {
        try {
            // 🔧 Configuration du seuil d’activité (par exemple, 7 jours)
            $daysThreshold = 7;
            $sinceDate = now()->subDays($daysThreshold);

            // 🔍 On suppose que l’activité des utilisateurs est enregistrée dans la table audit_logs
            // et qu’il y a une colonne user_id dans audit_logs
            $activeUserIds = \App\Models\AuditLog::where('created_at', '>=', $sinceDate)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            // 📊 Récupération du total des utilisateurs
            $totalUsers = \App\Models\User::count();

            // 🧮 Calcul des statistiques
            $activeUsersCount = count($activeUserIds);
            $inactiveUsersCount = $totalUsers - $activeUsersCount;

            // 🔁 Calcul du taux d’activité
            $activityRate = $totalUsers > 0 ? round(($activeUsersCount / $totalUsers) * 100, 2) : 0;

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsersCount,
                'inactive_users' => $inactiveUsersCount,
                'activity_rate' => $activityRate,
                'since_date' => $sinceDate->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans getActiveUsersStats : ' . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'activity_rate' => 0,
                'since_date' => now()->toDateTimeString(),
            ];
        }
    }

    public function getAPIUsageStats(string $period = '24h')
    {
        try {
            // 🔧 Définir la période (24h, 7d, 30d)
            switch ($period) {
                case '7d':
                    $since = now()->subDays(7);
                    break;
                case '30d':
                    $since = now()->subDays(30);
                    break;
                default:
                    $since = now()->subHours(24);
                    break;
            }

            // 🔍 On suppose que les logs API sont dans audit_logs avec additional_data->response_status
            $query = \App\Models\AuditLog::where('created_at', '>=', $since);

            // Total de requêtes
            $totalRequests = $query->count();

            // Requêtes ayant échoué (codes HTTP >= 400)
            $failedRequests = \App\Models\AuditLog::where('created_at', '>=', $since)
                ->whereRaw("JSON_EXTRACT(additional_data, '$.response_status') >= 400")
                ->count();

            // 🔢 Requêtes par utilisateur
            $requestsPerUser = \App\Models\AuditLog::selectRaw('user_id, COUNT(*) as total')
                ->where('created_at', '>=', $since)
                ->groupBy('user_id')
                ->pluck('total', 'user_id');

            $avgRequestsPerUser = $requestsPerUser->count() > 0
                ? round($requestsPerUser->avg(), 2)
                : 0;

            $errorRate = $totalRequests > 0
                ? round(($failedRequests / $totalRequests) * 100, 2)
                : 0;

            return [
                'total_requests' => $totalRequests,
                'failed_requests' => $failedRequests,
                'avg_per_user' => $avgRequestsPerUser,
                'error_rate' => $errorRate,
                'since' => $since->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans getAPIUsageStats : ' . $e->getMessage());
            return [
                'total_requests' => 0,
                'failed_requests' => 0,
                'avg_per_user' => 0,
                'error_rate' => 0,
                'since' => now()->toDateTimeString(),
            ];
        }
    }

    public function getSystemHealth(): array
    {
        try {
            // CPU : si sys_getloadavg indisponible, on met null
            $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
            $cpu = is_array($cpuLoad) ? round($cpuLoad[0], 2) : 0;

            // Mémoire
            $memoryUsage = memory_get_usage(true);
            $memoryLimitRaw = ini_get('memory_limit') ?: '512M';
            $memoryLimit = $this->convertToBytes($memoryLimitRaw);
            $memoryPercent = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;

            // Stockage (Disk Usage)
            $totalDisk = disk_total_space("/");
            $freeDisk = disk_free_space("/");
            $usedDisk = $totalDisk - $freeDisk;
            $diskPercent = round(($usedDisk / $totalDisk) * 100, 2);
            $storageWritable = is_writable(storage_path());

            // Base de données : vérifier si PDO fonctionne
            $dbStatus = false;
            try {
                $dbStatus = DB::connection()->getPdo() ? true : false;
            } catch (\Exception $e) {
                $dbStatus = false;
            }

            // Déterminer le statut global
            $status = 'healthy';
            $issues = [];

            if (!$dbStatus) {
                $status = 'critical';
                $issues[] = 'Base de données injoignable';
            }

            if ($cpu > 80) {
                $status = $status !== 'critical' ? 'warning' : $status;
                $issues[] = "CPU élevé ({$cpu}%)";
            }

            if ($memoryPercent > 80) {
                $status = $status !== 'critical' ? 'warning' : $status;
                $issues[] = "Mémoire saturée ({$memoryPercent}%)";
            }
            
            if ($diskPercent > 90) {
                $status = $status !== 'critical' ? 'warning' : $status;
                $issues[] = "Espace disque critique ({$diskPercent}%)";
            }

            if (!$storageWritable) {
                $status = 'critical';
                $issues[] = 'Répertoire de stockage (/storage) non scriptible';
            }

            return [
                'status' => $status,
                'cpu_usage' => [
                    'status' => $cpu > 80 ? 'warning' : 'healthy',
                    'message' => $cpu . '%',
                    'percent' => $cpu
                ],
                'memory_usage' => [
                    'status' => $memoryPercent > 80 ? 'warning' : 'healthy',
                    'message' => $memoryPercent . '%',
                    'percent' => $memoryPercent
                ],
                'storage' => [
                    'status' => (!$storageWritable) ? 'critical' : ($diskPercent > 90 ? 'warning' : 'healthy'),
                    'message' => $diskPercent . '% ' . ($storageWritable ? '(OK)' : '(Lecture seule)'),
                    'percent' => $diskPercent
                ],
                'database' => [
                    'status' => $dbStatus ? 'healthy' : 'critical',
                    'message' => $dbStatus ? 'Connecté' : 'Hors ligne'
                ],
                'environment' => [
                    'status' => 'healthy',
                    'message' => app()->environment()
                ],
                'php_version' => [
                    'status' => 'healthy',
                    'message' => PHP_VERSION
                ],
                'laravel_version' => [
                    'status' => 'healthy',
                    'message' => app()->version()
                ],
                'debug_mode' => [
                    'status' => config('app.debug') ? 'warning' : 'healthy',
                    'message' => config('app.debug') ? 'Activé (⚠️)' : 'Désactivé'
                ],
                'issues' => $issues,
                'timestamp' => now()->toDateTimeString(),
            ];

        } catch (\Throwable $e) {
            Log::error('Erreur getSystemHealth: ' . $e->getMessage());
            return [
                'status' => 'error',
                'cpu_usage' => [
                    'status' => 'error',
                    'message' => 'N/A',
                    'percent' => 0
                ],
                'memory_usage' => [
                    'status' => 'error',
                    'message' => 'N/A',
                    'percent' => 0
                ],
                'storage' => [
                    'status' => 'error',
                    'message' => 'N/A',
                    'percent' => 0
                ],
                'database' => [
                    'status' => 'error',
                    'message' => 'Inconnu'
                ],
                'environment' => [
                    'status' => 'error',
                    'message' => 'Inconnu'
                ],
                'php_version' => [
                    'status' => 'error',
                    'message' => 'Inconnu'
                ],
                'laravel_version' => [
                    'status' => 'error',
                    'message' => 'Inconnu'
                ],
                'debug_mode' => [
                    'status' => 'error',
                    'message' => 'Inconnu'
                ],
                'issues' => [$e->getMessage()],
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    public function getDatabaseStats(): array
    {
        try {
            // Utiliser DB::select pour obtenir les threads connectés et l'uptime pour MySQL
            $connections = 0;
            $uptime = 0;
            
            if (config('database.default') === 'mysql') {
                $connectionsStatus = DB::select('SHOW STATUS LIKE "Threads_connected"');
                $connections = !empty($connectionsStatus) ? $connectionsStatus[0]->Value : 0;
                
                $uptimeStatus = DB::select('SHOW STATUS LIKE "Uptime"');
                $uptime = !empty($uptimeStatus) ? $uptimeStatus[0]->Value : 0;
            }

            return [
                'connections' => (int) $connections,
                'uptime_seconds' => (int) $uptime,
                'status' => $connections >= 0 ? 'healthy' : 'unhealthy',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur getDatabaseStats: ' . $e->getMessage());
            return [
                'connections' => 0,
                'uptime_seconds' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCacheStats(): array
    {
        try {
            // Nombre de clés dans le cache (si Redis)
            $cacheStore = config('cache.default');
            $stats = [];

            if ($cacheStore === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $keys = $redis->keys('*');
                $stats['total_keys'] = count($keys);
                $stats['memory_usage'] = $redis->info('memory')['used_memory_human'] ?? 'N/A';
            } else {
                $stats['total_keys'] = null;
                $stats['memory_usage'] = null;
            }

            $stats['store'] = $cacheStore;
            $stats['timestamp'] = now()->toDateTimeString();
            return $stats;

        } catch (\Exception $e) {
            Log::error('Erreur getCacheStats: ' . $e->getMessage());
            return [
                'total_keys' => 0,
                'memory_usage' => 'N/A',
                'store' => config('cache.default'),
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Convertir valeur "512M" ou "2G" en octets
     */
    public function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }



    // --- Réécriture de getSecurityAlerts sans Redis ---
    public function getSecurityAlerts()
    {
        // On récupère les 1000 dernières alertes depuis la table 'security_alerts'
        $securityLogs = DB::table('security_alerts')
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        $alerts = [];
        $ipAttempts = [];

        foreach ($securityLogs as $log) {
            $ip = $log->ip;

            if (!isset($ipAttempts[$ip])) {
                $ipAttempts[$ip] = 0;
            }
            $ipAttempts[$ip]++;

            // Détecter les IPs suspectes (plus de 50 requêtes en 1h)
            if ($ipAttempts[$ip] > 50) {
                $alerts[] = [
                    'type' => 'suspicious_ip',
                    'ip' => $ip,
                    'attempts' => $ipAttempts[$ip],
                    'severity' => 'medium'
                ];
            }
        }

        return array_unique($alerts, SORT_REGULAR);
    }

    // Optionnel : méthode pour ajouter un log de sécurité
    public function addSecurityAlert($ip, $message)
    {
        DB::table('security_alerts')->insert([
            'ip' => $ip,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

     public function getQueueStats(): array
    {
        try {
            $queueConnections = config('queue.connections');
            $stats = [];
            foreach ($queueConnections as $name => $config) {
                $size = Queue::size($name) ?? 0;
                $stats[$name] = ['pending_jobs' => $size];
            }
            return $stats;
        } catch (\Exception $e) {
            Log::error('Erreur getQueueStats: ' . $e->getMessage());
            return [];
        }
    }

    // ----------------------------
    // Taux d’erreurs
    // ----------------------------
    public function getErrorRates(string $period = '24h'): array
    {
        try {
            $since = match($period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                default => now()->subHours(24),
            };

            $totalRequests = AuditLog::where('created_at', '>=', $since)->count();
            $failedRequests = AuditLog::where('created_at', '>=', $since)
                ->whereRaw("JSON_EXTRACT(additional_data, '$.response_status') >= 400")
                ->count();

            return [
                'total_requests' => $totalRequests,
                'failed_requests' => $failedRequests,
                'error_rate_percent' => $totalRequests > 0 ? round(($failedRequests / $totalRequests) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur getErrorRates: ' . $e->getMessage());
            return ['total_requests' => 0, 'failed_requests' => 0, 'error_rate_percent' => 0];
        }
    }

}

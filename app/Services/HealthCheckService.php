<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'app' => $this->checkApp(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        return [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'version' => '1.0.0',
            'checks' => $checks,
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');

            return $value === 'ok'
                ? ['status' => 'healthy', 'message' => 'Cache driver OK']
                : ['status' => 'unhealthy', 'message' => 'Cache read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            if ($connection === 'database') {
                DB::table('jobs')->count();
            }

            return ['status' => 'healthy', 'message' => "Queue driver [{$connection}] OK"];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_'.uniqid().'.txt';
            Storage::disk('local')->put($testFile, 'health_check');
            $content = Storage::disk('local')->get($testFile);
            Storage::disk('local')->delete($testFile);

            return $content === 'health_check'
                ? ['status' => 'healthy', 'message' => 'Storage read/write OK']
                : ['status' => 'unhealthy', 'message' => 'Storage read/write mismatch'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkApp(): array
    {
        return [
            'status' => 'healthy',
            'message' => 'Laravel '.app()->version().' running',
            'php_version' => PHP_VERSION,
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).' MB',
        ];
    }
}

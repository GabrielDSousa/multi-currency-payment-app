<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function __construct(
        private HealthCheckService $healthCheck
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->healthCheck->check();

        if (! $result['status'] === 'healthy') {
            abort(503, $result);
        }

        return response()->json($result, 200);
    }
}

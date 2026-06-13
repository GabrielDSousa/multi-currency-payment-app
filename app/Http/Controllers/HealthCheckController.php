<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HealthCheckService;

class HealthCheckController extends Controller
{
    public function __construct(
        private HealthCheckService $healthCheck
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $result = $this->healthCheck->check();
        $statusCode = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $statusCode);
    }
}

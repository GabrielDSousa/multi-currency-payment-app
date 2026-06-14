<?php

namespace App\Services;

use App\Models\ExchangeRateLog;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExchangeRateService
{
    private const BASE_CURRENCY = 'EUR';

    private const CACHE_TTL = 3600;

    private const API_TIMEOUT = 10;

    /**
     * Return the EUR → $currency exchange rate.
     * Result is cached for 1 hour. A failed call is never cached.
     *
     * @return array{ rate: float, source: string, timestamp: string }
     *
     * @throws RuntimeException when the API is unreachable or returns an error.
     */
    public function fetchRate(string $currency): array
    {
        $currency = strtoupper($currency);
        $cacheKey = 'exchange_rate_'.self::BASE_CURRENCY.'_'.$currency;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->callApi($currency)
        );
    }

    private function callApi(string $currency): array
    {
        $apiKey = config('services.exchangerate_api.key');
        $source = config('services.exchangerate_api.source');

        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->get("https://{$source}{$apiKey}/pair/".self::BASE_CURRENCY."/{$currency}");

            if ($response->failed()) {
                throw new RuntimeException(
                    "Exchange rate API returned HTTP {$response->status()} for EUR→{$currency}."
                );
            }

            $data = $response->json();

            if (($data['result'] ?? '') !== 'success') {
                throw new RuntimeException(
                    "Exchange rate API error for EUR→{$currency}: ".($data['error-type'] ?? 'unknown')
                );
            }

            $result = [
                'rate' => (float) $data['conversion_rate'],
                'source' => config('services.exchangerate_api.source'),
                'timestamp' => Carbon::now()->toISOString(),
            ];

            $this->log($currency, $result['rate']);

            return $result;
        } catch (ConnectionException $e) {
            Log::error("ExchangeRateService: connection failed for EUR→{$currency}", [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                "Cannot connect to exchange rate API: {$e->getMessage()}",
                previous: $e
            );
        } catch (RuntimeException $e) {
            Log::error("ExchangeRateService: failed to fetch EUR→{$currency}", [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function log(string $currency, float $rate): void
    {
        ExchangeRateLog::create([
            'base_currency' => self::BASE_CURRENCY,
            'target_currency' => $currency,
            'rate' => $rate,
            'source' => config('services.exchangerate_api.source'),
            'fetched_at' => now(),
        ]);
    }
}

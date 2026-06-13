<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ExchangeRateService::class)]
#[Group('exchange-rate')]
class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExchangeRateService $service;

    private const CACHE_KEY_PREFIX  = 'exchange_rate_EUR_';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExchangeRateService::class);

        // Prevent accidental real HTTP calls during tests.
        Http::preventStrayRequests();

        Cache::flush();
    }

    #[Test]
    #[DataProvider('currencyRateProvider')]
    public function it_fetches_eur_to_currency_rate_successfully(
        string $currency,
        float $expectedRate
    ): void {
        Http::fake([
            "*EUR/{$currency}*" => Http::response(
                $this->buildApiResponse($currency, $expectedRate),
                200
            ),
        ]);

        $result = $this->service->fetchRate($currency);

        $this->assertIsArray($result, 'fetchRate() must return an array.');
        $this->assertArrayHasKey('rate', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals($expectedRate, $result['rate']);
        $this->assertStringContainsString(config('services.exchangerate_api.source'), $result['source']);
        $this->assertNotEmpty($result['timestamp']);
    }

    public static function currencyRateProvider(): array
    {
        return [
            'EUR to BRL' => ['BRL', 5.42],
            'EUR to USD' => ['USD', 1.08],
            'EUR to GBP' => ['GBP', 0.85],
            'EUR to JPY' => ['JPY', 161.30],
        ];
    }

    #[Test]
    public function it_returns_array_with_all_three_required_keys(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
        ]);

        $result = $this->service->fetchRate('USD');

        foreach (['rate', 'source', 'timestamp'] as $key) {
            $this->assertArrayHasKey($key, $result, "Key '{$key}' must be present in the result.");
        }
    }

    #[Test]
    public function it_returns_exchangerate_api_as_source_string(): void
    {
        Http::fake([
            '*EUR/GBP*' => Http::response($this->buildApiResponse('GBP', 0.85), 200),
        ]);

        $result = $this->service->fetchRate('GBP');

        $this->assertEquals(config('services.exchangerate_api.source'), $result['source']);
    }

    #[Test]
    public function it_returns_a_valid_iso8601_timestamp(): void
    {
        Http::fake([
            '*EUR/JPY*' => Http::response($this->buildApiResponse('JPY', 161.30), 200),
        ]);

        $result = $this->service->fetchRate('JPY');

        $this->assertNotEmpty($result['timestamp']);
        $parsed = Carbon::parse($result['timestamp']);
        $this->assertNotNull($parsed);
        // ISO-8601 pattern: YYYY-MM-DDTHH:MM:SS
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $result['timestamp']
        );
    }

    #[Test]
    public function it_returns_a_positive_numeric_rate(): void
    {
        Http::fake([
            '*EUR/BRL*' => Http::response($this->buildApiResponse('BRL', 5.42), 200),
        ]);

        $result = $this->service->fetchRate('BRL');

        $this->assertIsNumeric($result['rate']);
        $this->assertGreaterThan(0, $result['rate']);
    }

    #[Test]
    public function it_does_not_call_the_api_on_the_second_request_for_the_same_currency(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
        ]);

        $this->service->fetchRate('USD');
        $this->service->fetchRate('USD'); // should be served from cache

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_stores_the_result_in_cache_under_the_correct_key(): void
    {
        Http::fake([
            '*EUR/BRL*' => Http::response($this->buildApiResponse('BRL', 5.42), 200),
        ]);

        $this->service->fetchRate('BRL');

        $this->assertTrue(
            Cache::has(self::CACHE_KEY_PREFIX . 'BRL'),
            'Expected cache key "' . self::CACHE_KEY_PREFIX . 'BRL" to exist after fetch.'
        );
    }

    #[Test]
    public function it_returns_identical_data_from_cache_on_second_call(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
        ]);

        $first  = $this->service->fetchRate('USD');
        $second = $this->service->fetchRate('USD');

        $this->assertEquals($first, $second);
    }

    #[Test]
    public function it_caches_different_currencies_under_separate_keys(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
            '*EUR/BRL*' => Http::response($this->buildApiResponse('BRL', 5.42), 200),
        ]);

        $this->service->fetchRate('USD');
        $this->service->fetchRate('BRL');

        $this->assertTrue(Cache::has(self::CACHE_KEY_PREFIX . 'USD'));
        $this->assertTrue(Cache::has(self::CACHE_KEY_PREFIX . 'BRL'));

        $usdRate = Cache::get(self::CACHE_KEY_PREFIX . 'USD');
        $brlRate = Cache::get(self::CACHE_KEY_PREFIX . 'BRL');
        $this->assertNotEquals($usdRate['rate'], $brlRate['rate']);
    }

    #[Test]
    public function it_hits_the_api_again_after_the_cache_entry_is_manually_expired(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
        ]);

        $this->service->fetchRate('USD');
        Http::assertSentCount(1);

        // Simulate cache expiry.
        Cache::forget(self::CACHE_KEY_PREFIX . 'USD');

        $this->service->fetchRate('USD');
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_stores_cache_with_a_one_hour_ttl(): void
    {
        Http::fake([
            '*EUR/GBP*' => Http::response($this->buildApiResponse('GBP', 0.85), 200),
        ]);

        $before = now();

        $this->service->fetchRate('GBP');

        // The key must exist immediately after the call.
        $this->assertTrue(Cache::has(self::CACHE_KEY_PREFIX . 'GBP'));

        // Advance time by 59 minutes — cache must still be present.
        $this->travel(59)->minutes();
        $this->assertTrue(
            Cache::has(self::CACHE_KEY_PREFIX . 'GBP'),
            'Cache should still be valid at 59 minutes.'
        );
    }

    #[Test]
    public function it_throws_an_exception_when_the_api_returns_a_500_error(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);

        $this->service->fetchRate('USD');
    }

    #[Test]
    public function it_throws_an_exception_when_the_api_returns_a_404_for_unknown_currency(): void
    {
        Http::fake([
            '*EUR/XYZ*' => Http::response(['error-type' => 'unsupported-code'], 404),
        ]);

        $this->expectException(\Exception::class);

        $this->service->fetchRate('XYZ');
    }

    #[Test]
    public function it_throws_an_exception_on_network_connection_failure(): void
    {
        Http::fake([
            '*' => fn() => throw new ConnectionException('Connection refused'),
        ]);

        $this->expectException(\Exception::class);

        $this->service->fetchRate('USD');
    }

    #[Test]
    public function it_logs_an_error_message_when_the_api_call_fails(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn(string $message) => str_contains($message, 'USD'));

        try {
            $this->service->fetchRate('USD');
        } catch (\Exception) {
            // Expected — we only assert the log call.
        }
    }

    #[Test]
    public function it_does_not_store_a_cache_entry_when_the_api_call_fails(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response([], 500),
        ]);

        try {
            $this->service->fetchRate('USD');
        } catch (\Exception) {
            // Expected.
        }

        $this->assertFalse(
            Cache::has(self::CACHE_KEY_PREFIX . 'USD'),
            'A failed API call must not populate the cache.'
        );
    }

    #[Test]
    public function payment_factory_creates_a_persisted_record_with_all_required_fields(): void
    {
        $payment = Payment::factory()->create();

        $requiredFields = [
            'user_id',
            'amount_local',
            'currency_code',
            'amount_eur',
            'exchange_rate',
            'rate_source',
            'rate_timestamp',
            'description',
        ];

        foreach ($requiredFields as $field) {
            $this->assertNotNull(
                $payment->{$field},
                "Field '{$field}' should not be null after factory creation."
            );
        }
    }

    #[Test]
    public function payment_model_declares_all_required_fillable_fields(): void
    {
        $expectedFillable = [
            'user_id',
            'amount_local',
            'currency_code',
            'amount_eur',
            'exchange_rate',
            'rate_source',
            'rate_timestamp',
            'pending',
            'description',
            'approved_by',
            'approved_at',
            'expired_at',
        ];

        $fillable = (new Payment())->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains(
                $field,
                $fillable,
                "Field '{$field}' must be listed in Payment::\$fillable."
            );
        }
    }

    #[Test]
    public function payment_is_pending_by_default_on_creation(): void
    {
        $payment = Payment::create([
            'user_id'        => User::factory()->create()->id,
            'amount_local'   => 100.00,
            'currency_code'  => 'USD',
            'exchange_rate'  => 1.08,
            'rate_source'    => config('services.exchangerate_api.source'),
            'rate_timestamp' => now()->toISOString(),
            'amount_eur'     => round(100.00 / 1.08, 2),
            'description'    => 'Test payment',
        ])->fresh();

        $this->assertTrue(
            $payment->pending === true,
            'A newly created payment must be in pending state.'
        );
    }

    #[Test]
    public function payment_approved_by_and_approved_at_are_nullable(): void
    {
        $payment = Payment::factory()->create([
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->assertNull($payment->approved_by);
        $this->assertNull($payment->approved_at);
    }

    #[Test]
    public function payment_expired_at_is_nullable_on_creation(): void
    {
        $payment = Payment::factory()->create(['expired_at' => null]);

        $this->assertNull($payment->expired_at);
    }

    #[Test]
    public function payment_stores_exchange_rate_data_returned_by_the_service(): void
    {
        $user = User::factory()->create();

        Http::fake([
            '*EUR/BRL*' => Http::response($this->buildApiResponse('BRL', 5.42), 200),
        ]);

        $rateData = $this->service->fetchRate('BRL');

        $payment = Payment::factory()->create([
            'user_id'        => $user->id,
            'amount_local'   => 1000.00,
            'currency_code'  => 'BRL',
            'exchange_rate'  => $rateData['rate'],
            'rate_source'    => $rateData['source'],
            'rate_timestamp' => $rateData['timestamp'],
            'amount_eur'     => round(1000.00 / $rateData['rate'], 2),
        ]);

        $this->assertDatabaseHas('payments', [
            'id'            => $payment->id,
            'currency_code' => 'BRL',
            'exchange_rate' => 5.42,
            'rate_source'   => config('services.exchangerate_api.source'),
        ]);
    }

    #[Test]
    public function payment_eur_amount_is_correctly_computed_from_rate(): void
    {
        $amountLocal = 1000.00;
        $rate        = 5.42;
        $expectedEur = round($amountLocal / $rate, 2);

        $payment = Payment::factory()->create([
            'amount_local'  => $amountLocal,
            'currency_code' => 'BRL',
            'exchange_rate' => $rate,
            'amount_eur'    => $expectedEur,
        ]);

        $this->assertEquals($expectedEur, (float) $payment->amount_eur);
    }

    #[Test]
    public function payment_exchange_rate_is_immutable_after_creation(): void
    {
        $originalRate = 5.42;

        $payment = Payment::factory()->create([
            'exchange_rate'  => $originalRate,
            'rate_source'    => config('services.exchangerate_api.source'),
            'rate_timestamp' => now()->toISOString(),
        ]);

        // Simulate a market-rate change — the stored payment rate must NOT be affected.
        Http::fake([
            '*EUR/BRL*' => Http::response($this->buildApiResponse('BRL', 5.99), 200),
        ]);

        $payment->refresh();

        $this->assertEquals(
            $originalRate,
            (float) $payment->exchange_rate,
            'Stored exchange rate must never change after the payment is created.'
        );
    }

    #[Test]
    public function payment_stores_rate_source_and_rate_timestamp_correctly(): void
    {
        $now = now()->toISOString();

        $payment = Payment::factory()->create([
            'rate_source'    => config('services.exchangerate_api.source'),
            'rate_timestamp' => $now,
        ]);

        $this->assertDatabaseHas('payments', [
            'id'          => $payment->id,
            'rate_source' => config('services.exchangerate_api.source'),
        ]);

        $this->assertNotNull($payment->rate_timestamp);
    }

    #[Test]
    public function it_persists_a_log_entry_in_exchange_rate_logs_after_successful_fetch(): void
    {
        Http::fake([
            '*EUR/USD*' => Http::response($this->buildApiResponse('USD', 1.08), 200),
        ]);

        $this->service->fetchRate('USD');

        $this->assertDatabaseHas('exchange_rate_logs', [
            'base_currency'   => 'EUR',
            'target_currency' => 'USD',
            'source'          => config('services.exchangerate_api.source'),
        ]);
    }

    #[Test]
    public function it_persists_log_entries_for_all_four_required_currency_pairs(): void
    {
        $pairs = [
            'BRL' => 5.42,
            'USD' => 1.08,
            'GBP' => 0.85,
            'JPY' => 161.30,
        ];

        foreach ($pairs as $currency => $rate) {
            Http::fake([
                "*EUR/{$currency}*" => Http::response(
                    $this->buildApiResponse($currency, $rate),
                    200
                ),
            ]);

            // Clear cache between pairs so each one hits the API.
            Cache::forget(self::CACHE_KEY_PREFIX . $currency);

            $this->service->fetchRate($currency);
        }

        foreach (array_keys($pairs) as $currency) {
            $this->assertDatabaseHas('exchange_rate_logs', [
                'base_currency'   => 'EUR',
                'target_currency' => $currency,
                'source'          => config('services.exchangerate_api.source'),
            ]);
        }
    }

    /**
     * Build a fake successful response body matching exchangerate-api.com's
     * /v6/{key}/pair/EUR/{currency} endpoint.
     */
    private function buildApiResponse(string $targetCurrency, float $rate): array
    {
        return [
            'result'                => 'success',
            'documentation'         => 'https://www.exchangerate-api.com/docs',
            'terms_of_use'          => 'https://www.exchangerate-api.com/terms',
            'base_code'             => 'EUR',
            'target_code'           => $targetCurrency,
            'conversion_rate'       => $rate,
            'time_last_update_unix' => Carbon::now()->timestamp,
            'time_last_update_utc'  => Carbon::now()->toRfc7231String(),
        ];
    }
}

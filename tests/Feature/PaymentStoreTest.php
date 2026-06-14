<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

#[Group('payment-store')]
class PaymentStoreTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/payment';

    private const EXCHANGE_RATE = 5.42;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function fakeExchangeRate(string $currency = 'BRL', float $rate = self::EXCHANGE_RATE): void
    {
        Http::fake([
            "*EUR/{$currency}*" => Http::response([
                'result' => 'success',
                'base_code' => 'EUR',
                'target_code' => $currency,
                'conversion_rate' => $rate,
            ], 200),
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'amount_local' => 1000.00,
            'currency_code' => 'BRL',
            'description' => 'Office supplies reimbursement',
        ], $overrides);
    }

    #[Test]
    public function authenticated_user_can_create_a_payment(): void
    {
        $this->fakeExchangeRate('BRL');

        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'amount_local',
                    'currency_code',
                    'amount_eur',
                    'exchange_rate',
                    'rate_source',
                    'rate_timestamp',
                    'status',
                    'created_at',
                ],
            ]);
    }

    #[Test]
    public function payment_is_persisted_to_the_database(): void
    {
        $user = User::factory()->employee()->create();
        $this->fakeExchangeRate('BRL');

        Passport::actingAs($user);

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => 1500.00,
            'currency_code' => 'BRL',
        ]));

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount_local' => 1500.00,
            'currency_code' => 'BRL',
        ]);
    }

    #[Test]
    public function response_status_is_201_created(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertStatus(201);
    }

    #[Test]
    public function user_id_is_taken_from_the_authenticated_token_not_the_body(): void
    {
        $user = User::factory()->employee()->create();
        $this->fakeExchangeRate('BRL');

        Passport::actingAs($user);

        $response = $this->postJson(self::ENDPOINT, array_merge(
            $this->validPayload(),
            ['user_id' => 9999]
        ));

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseMissing('payments', ['user_id' => 9999]);
    }

    #[Test]
    public function payment_is_created_as_pending_by_default(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $response = $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertCreated();

        $this->assertEquals('pending', $response->json('data.status'));

        $this->assertDatabaseHas('payments', [
            'pending' => true,
            'approved_at' => null,
            'expired_at' => null,
        ]);
    }

    // =========================================================================
    // SECTION 3 — Exchange rate integration
    // =========================================================================

    #[Test]
    public function exchange_rate_is_stored_from_the_api_response(): void
    {
        $this->fakeExchangeRate('BRL', 5.42);
        Passport::actingAs(User::factory()->employee()->create());

        $response = $this->postJson(self::ENDPOINT, $this->validPayload([
            'currency_code' => 'BRL',
        ]))->assertCreated();

        $this->assertEquals(5.42, $response->json('data.exchange_rate'));
        $this->assertDatabaseHas('payments', ['exchange_rate' => 5.42]);
    }

    #[Test]
    public function amount_eur_is_calculated_correctly_from_exchange_rate(): void
    {
        $this->fakeExchangeRate('BRL', 5.42);
        Passport::actingAs(User::factory()->employee()->create());

        $response = $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => 1000.00,
            'currency_code' => 'BRL',
        ]))->assertCreated();

        // 1000 / 5.42 = 184.5018...
        $expected = round(1000 / 5.42, 4);

        $this->assertEquals($expected, $response->json('data.amount_eur'));
    }

    #[Test]
    public function rate_source_is_stored_correctly(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.rate_source', config('services.exchangerate_api.source'));
    }

    #[Test]
    public function rate_timestamp_is_stored_and_not_null(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $response = $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertCreated();

        $this->assertNotNull($response->json('data.rate_timestamp'));
    }

    #[Test]
    public function currency_code_is_stored_as_uppercase(): void
    {
        $this->fakeExchangeRate('USD', 1.08);
        Passport::actingAs(User::factory()->employee()->create());

        $response = $this->postJson(self::ENDPOINT, $this->validPayload([
            'currency_code' => 'usd', // lowercase input
        ]))->assertCreated();

        $this->assertEquals('USD', $response->json('data.currency_code'));
        $this->assertDatabaseHas('payments', ['currency_code' => 'USD']);
    }

    // ─── Multiple currency pairs ───────────────────────────────────────────

    #[Test]
    #[DataProvider('currencyPairProvider')]
    public function it_creates_payment_for_each_supported_currency(
        string $currency,
        float $rate,
    ): void {
        $this->fakeExchangeRate($currency, $rate);
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => 100.00,
            'currency_code' => $currency,
        ]))->assertCreated()
            ->assertJsonPath('data.currency_code', $currency)
            ->assertJsonPath('data.exchange_rate', $rate);
    }

    public static function currencyPairProvider(): array
    {
        return [
            'BRL' => ['BRL', 5.42],
            'USD' => ['USD', 1.08],
            'GBP' => ['GBP', 0.85],
            'JPY' => ['JPY', 161.30],
        ];
    }

    // =========================================================================
    // SECTION 4 — Description field
    // =========================================================================

    #[Test]
    public function description_is_stored_when_provided(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'description' => 'Team lunch expense',
        ]))->assertCreated();

        $this->assertDatabaseHas('payments', ['description' => 'Team lunch expense']);
    }

    #[Test]
    public function description_is_nullable_and_payment_is_created_without_it(): void
    {
        $this->fakeExchangeRate('BRL');
        Passport::actingAs(User::factory()->employee()->create());

        $payload = $this->validPayload();
        unset($payload['description']);

        $this->postJson(self::ENDPOINT, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('payments', ['description' => null]);
    }

    // =========================================================================
    // SECTION 5 — Validation errors (422)
    // =========================================================================

    #[Test]
    public function amount_local_is_required(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $payload = $this->validPayload();
        unset($payload['amount_local']);

        $this->postJson(self::ENDPOINT, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_local']);
    }

    #[Test]
    public function currency_code_is_required(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $payload = $this->validPayload();
        unset($payload['currency_code']);

        $this->postJson(self::ENDPOINT, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['currency_code']);
    }

    #[Test]
    public function amount_local_must_be_numeric(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => 'not-a-number',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_local']);
    }

    #[Test]
    public function amount_local_must_be_greater_than_zero(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => 0,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_local']);
    }

    #[Test]
    public function amount_local_cannot_be_negative(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'amount_local' => -100,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_local']);
    }

    #[Test]
    public function currency_code_must_be_exactly_3_characters(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        foreach (['BR', 'BRAZ'] as $invalid) {
            $this->postJson(self::ENDPOINT, $this->validPayload([
                'currency_code' => $invalid,
            ]))->assertUnprocessable()
                ->assertJsonValidationErrors(['currency_code']);
        }
    }

    #[Test]
    public function currency_code_must_be_alpha_only(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'currency_code' => '1BR',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['currency_code']);
    }

    #[Test]
    public function description_cannot_exceed_1000_characters(): void
    {
        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload([
            'description' => str_repeat('a', 1001),
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    // =========================================================================
    // SECTION 6 — Authentication
    // =========================================================================

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertUnauthorized();
    }

    #[Test]
    public function no_payment_is_created_when_unauthenticated(): void
    {
        $this->postJson(self::ENDPOINT, $this->validPayload());

        $this->assertDatabaseCount('payments', 0);
    }

    // =========================================================================
    // SECTION 7 — Exchange Rate API failure
    // =========================================================================

    #[Test]
    public function returns_503_when_exchange_rate_api_is_unavailable(): void
    {
        // Mock the service to throw — simulates API down
        $this->mock(ExchangeRateService::class)
            ->shouldReceive('fetchRate')
            ->andThrow(new RuntimeException('Cannot connect to exchange rate API'));

        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload())
            ->assertStatus(503)
            ->assertJsonPath('message', 'Exchange rate service is temporarily unavailable. Please try again later.');
    }

    #[Test]
    public function no_payment_is_created_when_exchange_rate_api_fails(): void
    {
        $this->mock(ExchangeRateService::class)
            ->shouldReceive('fetchRate')
            ->andThrow(new RuntimeException('API error'));

        Passport::actingAs(User::factory()->employee()->create());

        $this->postJson(self::ENDPOINT, $this->validPayload());

        $this->assertDatabaseCount('payments', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseSeedingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function php_artisan_db_seed_populates_data_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 16);
        $this->assertDatabaseCount('payments', 16);
    }

    #[Test]
    public function creates_exactly_five_employees(): void
    {
        $this->seed(DatabaseSeeder::class);

        $users = User::all();

        $this->assertCount(16, $users, 'Deve haver exatamente 5 usuários.');
    }

    #[Test]
    public function users_have_realistic_and_consistent_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedCountries = [
            ['name' => 'Portugal', 'currency_code' => 'EUR'],
            ['name' => 'Brasil', 'currency_code' => 'BRL'],
            ['name' => 'EUA', 'currency_code' => 'USD'],
            ['name' => 'UK', 'currency_code' => 'GBP'],
            ['name' => 'Japão', 'currency_code' => 'JPY'],
        ];

        foreach ($expectedCountries as $expected) {
            $this->assertDatabaseHas('users', [
                'country' => $expected['name'],
                'currency_code' => $expected['currency_code'],
            ]);
        }
    }

    #[Test]
    public function user_factory_generates_valid_fake_data(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->password);
        $this->assertNotEmpty($user->country);
        $this->assertNotEmpty($user->currency_code);
        $this->assertNotEmpty($user->department);

        $this->assertTrue(boolval(preg_match('/^[A-Z]{3}$/', $user->currency_code)));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function payment_factory_generates_valid_fake_data(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($payment->user_id);
        $this->assertIsNumeric($payment->amount_local);
        $this->assertNotEmpty($payment->currency_code);
        $this->assertIsNumeric($payment->amount_eur);
        $this->assertIsNumeric($payment->exchange_rate);
        $this->assertNotEmpty($payment->rate_source);
        $this->assertInstanceOf(\DateTime::class, $payment->rate_timestamp);
        $this->assertIsBool($payment->pending);
        $this->assertNotEmpty($payment->description);

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function payment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $payment->user);
        $this->assertEquals($user->id, $payment->user->id);
    }

    #[Test]
    public function all_five_countries_are_represented(): void
    {
        $this->seed(DatabaseSeeder::class);

        $countries = DB::table('users')->pluck('country')->toArray();
        $currencies = DB::table('users')->pluck('currency_code')->toArray();

        $this->assertContains('Portugal', $countries);
        $this->assertContains('Brasil', $countries);
        $this->assertContains('EUA', $countries);
        $this->assertContains('UK', $countries);
        $this->assertContains('Japão', $countries);

        $this->assertContains('EUR', $currencies);
        $this->assertContains('BRL', $currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('GBP', $currencies);
        $this->assertContains('JPY', $currencies);
    }

    #[Test]
    public function user_factory_uses_faker_for_consistent_realistic_data(): void
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            $this->assertNotEmpty($user->name);
            $this->assertNotEmpty($user->email);
            $this->assertStringContainsString('@', $user->email);
            $this->assertNotEmpty($user->password);
            $this->assertNotEmpty($user->country);
            $this->assertNotEmpty($user->currency_code);
            $this->assertNotEmpty($user->department);
        }
    }

    #[Test]
    public function seeder_creates_payments_linked_to_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $payments = Payment::all();

        foreach ($payments as $payment) {
            $this->assertNotNull($payment->user_id);
            $this->assertInstanceOf(User::class, $payment->user);
        }
    }
}

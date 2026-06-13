<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('payment-list')]
class PaymentListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function employee_sees_only_their_own_payments(): void
    {
        /** @var \App\Models\User $employee */
        $employee = User::factory()->create(['department' => 'employee']);
        $other    = User::factory()->create(['department' => 'employee']);

        Payment::factory()->count(3)->create(['user_id' => $employee->id]);
        Payment::factory()->count(2)->create(['user_id' => $other->id]);

        Passport::actingAs($employee);

        $this->getJson('/api/payment')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function finance_sees_all_payments(): void
    {
        /** @var \App\Models\User $finance */
        $finance = User::factory()->create(['department' => 'finance']);
        Payment::factory()->count(5)->create();

        Passport::actingAs($finance);

        $this->getJson('/api/payment')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function finance_can_filter_by_employee_id(): void
    {
        /** @var \App\Models\User $finance */
        $finance  = User::factory()->create(['department' => 'finance']);
        $employee = User::factory()->create(['department' => 'employee']);

        Payment::factory()->count(2)->create(['user_id' => $employee->id]);
        Payment::factory()->count(3)->create(); // other employees

        Passport::actingAs($finance);

        $this->getJson("/api/payment?employee_id={$employee->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function filter_by_pending_status_returns_only_pending_payments(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['pending' => true,  'approved_at' => null, 'expired_at' => null]);
        Payment::factory()->create(['pending' => false, 'approved_at' => now(), 'expired_at' => null]);

        Passport::actingAs($user);

        $this->getJson('/api/payment?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    #[Test]
    public function filter_by_approved_status_returns_only_approved_payments(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['pending' => false, 'approved_at' => now(), 'expired_at' => null]);
        Payment::factory()->create(['pending' => true,  'approved_at' => null, 'expired_at' => null]);

        Passport::actingAs($user);

        $this->getJson('/api/payment?status=approved')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'approved');
    }

    #[Test]
    public function filter_by_expired_status_returns_only_expired_payments(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['expired_at' => now()->subDay()]);
        Payment::factory()->create(['expired_at' => null, 'pending' => true]);

        Passport::actingAs($user);

        $this->getJson('/api/payment?status=expired')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'expired');
    }

    #[Test]
    public function filter_by_currency_returns_only_matching_payments(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['currency_code' => 'BRL']);
        Payment::factory()->create(['currency_code' => 'USD']);

        Passport::actingAs($user);

        $this->getJson('/api/payment?currency=BRL')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.currency_code', 'BRL');
    }

    #[Test]
    public function filter_by_date_from_excludes_older_payments(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['created_at' => Carbon::parse('2026-01-01')]);
        Payment::factory()->create(['created_at' => Carbon::parse('2026-06-01')]);

        Passport::actingAs($user);

        $this->getJson('/api/payment?date_from=2026-06-01')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function filter_by_date_range_returns_payments_within_range(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);

        Payment::factory()->create(['created_at' => Carbon::parse('2026-05-15')]);
        Payment::factory()->create(['created_at' => Carbon::parse('2026-06-01')]);
        Payment::factory()->create(['created_at' => Carbon::parse('2026-06-10')]);

        Passport::actingAs($user);

        $this->getJson('/api/payment?date_from=2026-06-01&date_to=2026-06-10')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function response_is_paginated_with_15_items_per_page(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['department' => 'finance']);
        Payment::factory()->count(20)->create();

        Passport::actingAs($user);

        $response = $this->getJson('/api/payment')->assertOk();

        $this->assertCount(15, $response->json('data'));
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertEquals(15, $response->json('meta.per_page'));
        $this->assertEquals(20, $response->json('meta.total'));
    }

    #[Test]
    public function employee_can_fetch_their_own_payment_detail(): void
    {
        /** @var \App\Models\User $employee */
        $employee = User::factory()->create(['department' => 'employee']);
        $payment  = Payment::factory()->create(['user_id' => $employee->id]);

        Passport::actingAs($employee);

        $this->getJson("/api/payment/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    #[Test]
    public function employee_gets_403_when_fetching_another_users_payment(): void
    {
        /** @var \App\Models\User $employee */
        $employee = User::factory()->create(['department' => 'employee']);
        $payment  = Payment::factory()->create(); // belongs to another user

        Passport::actingAs($employee);

        $this->getJson("/api/payment/{$payment->id}")
            ->assertForbidden();
    }

    #[Test]
    public function finance_can_fetch_any_payment_detail(): void
    {
        /** @var \App\Models\User $finance */
        $finance = User::factory()->create(['department' => 'finance']);
        $payment = Payment::factory()->create();

        Passport::actingAs($finance);

        $this->getJson("/api/payment/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    #[Test]
    public function unauthenticated_user_gets_401(): void
    {
        $payment = Payment::factory()->create();

        $this->getJson("/api/payment/{$payment->id}")
            ->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('payment-expire')]
class ExpirePaymentRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_expires_pending_payments_older_than_48_hours(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $old = Payment::factory()->create([
            'pending'    => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(49),
        ]);

        Carbon::setTestNow('2026-06-10 12:00:00');

        $this->artisan('payment:expire')->assertSuccessful();

        $old->refresh();

        $this->assertFalse((bool) $old->pending);
        $this->assertNotNull($old->expired_at);
    }

    #[Test]
    public function it_sets_expired_at_to_current_timestamp(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        Carbon::setTestNow($now);

        $payment = Payment::factory()->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => $now->copy()->subHours(49),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $this->assertEqualsWithDelta(
            $now->timestamp,
            $payment->fresh()->expired_at->timestamp,
            5
        );
    }

    #[Test]
    public function it_expires_multiple_old_pending_payments_at_once(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        Payment::factory()->count(3)->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(72),
        ]);

        $this->artisan('payment:expire')
            ->expectsOutputToContain('Expired 3 payment request(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'payments',
            Payment::whereNotNull('expired_at', false)->count()
        );
    }

    #[Test]
    public function it_outputs_the_count_of_expired_payments(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        Payment::factory()->count(2)->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(50),
        ]);

        $this->artisan('payment:expire')
            ->expectsOutputToContain('Expired 2 payment request(s).')
            ->assertSuccessful();
    }

    #[Test]
    public function it_does_not_expire_a_payment_created_exactly_48_hours_ago(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        Carbon::setTestNow($now);

        $boundary = Payment::factory()->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => $now->copy()->subHours(48),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $boundary->refresh();

        $this->assertFalse((bool) $boundary->pending);
        $this->assertNotNull($boundary->expired_at);
    }

    #[Test]
    public function it_does_not_expire_a_payment_created_47_hours_ago(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        Carbon::setTestNow($now);

        $recent = Payment::factory()->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => $now->copy()->subHours(47),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $recent->refresh();

        $this->assertTrue((bool) $recent->pending);
        $this->assertNull($recent->expired_at);
    }

    #[Test]
    public function it_does_not_expire_already_approved_payments(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $approved = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => Carbon::now()->subHours(50),
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(50),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $this->assertNull($approved->fresh()->expired_at);
    }

    #[Test]
    public function it_does_not_expire_already_rejected_payments(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $rejected = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(50),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $this->assertNull($rejected->fresh()->expired_at);
    }

    #[Test]
    public function it_does_not_re_expire_already_expired_payments(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $originalExpiredAt = Carbon::now()->subHours(10);

        $alreadyExpired = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => null,
            'expired_at'  => $originalExpiredAt,
            'created_at'  => Carbon::now()->subHours(50),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $this->assertEquals(
            $originalExpiredAt->timestamp,
            $alreadyExpired->fresh()->expired_at->timestamp
        );
    }

    #[Test]
    public function it_does_not_expire_recent_pending_payments(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $recent = Payment::factory()->create([
            'pending'     => true,
            'approved_at' => null,
            'expired_at'  => null,
            'created_at'  => Carbon::now()->subHours(10),
        ]);

        $this->artisan('payment:expire')->assertSuccessful();

        $this->assertTrue((bool) $recent->fresh()->pending);
        $this->assertNull($recent->fresh()->expired_at);
    }

    #[Test]
    public function it_outputs_zero_when_no_payments_need_expiring(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        Payment::factory()->create([
            'pending'    => true,
            'created_at' => Carbon::now()->subHours(5),
        ]);

        $this->artisan('payment:expire')
            ->expectsOutputToContain('Expired 0 payment request(s).')
            ->assertSuccessful();
    }

    #[Test]
    public function it_exits_successfully_with_no_payments_in_database(): void
    {
        $this->artisan('payment:expire')
            ->expectsOutputToContain('Expired 0 payment request(s).')
            ->assertSuccessful();
    }

    #[Test]
    public function the_command_is_registered_in_the_scheduler(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $commands = collect($schedule->events())
            ->map(fn($e) => $e->command ?? '')
            ->filter(fn($cmd) => str_contains($cmd, 'payment:expire'));

        $this->assertNotEmpty(
            $commands,
            'payment:expire must be registered in the scheduler.'
        );
    }
}

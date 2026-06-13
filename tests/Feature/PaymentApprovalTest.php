<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('payment-approval')]
class PaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function financeUser(): User
    {
        return User::factory()->create(['department' => 'finance']);
    }

    private function employeeUser(): User
    {
        return User::factory()->create(['department' => 'employee']);
    }

    private function pendingPayment(array $overrides = []): Payment
    {
        return Payment::factory()->create(array_merge([
            'pending'     => true,
            'approved_at' => null,
            'approved_by' => null,
            'expired_at'  => null,
        ], $overrides));
    }

    // =========================================================================
    // APPROVE — happy path
    // =========================================================================

    #[Test]
    public function finance_can_approve_a_pending_payment(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('payments', [
            'id'          => $payment->id,
            'pending'     => false,
            'approved_by' => $finance->id,
        ]);

        $this->assertNotNull($payment->fresh()->approved_at);
    }

    #[Test]
    public function approve_sets_approved_by_to_the_finance_user_id(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.approved_by', $finance->id);
    }

    #[Test]
    public function approve_sets_approved_at_timestamp(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertOk();

        $this->assertNotNull($payment->fresh()->approved_at);
    }

    #[Test]
    public function approve_sets_pending_to_false(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")->assertOk();

        $this->assertFalse((bool) $payment->fresh()->pending);
    }

    // =========================================================================
    // REJECT — happy path
    // =========================================================================

    #[Test]
    public function finance_can_reject_a_pending_payment(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('payments', [
            'id'          => $payment->id,
            'pending'     => false,
            'approved_by' => $finance->id,
        ]);
    }

    #[Test]
    public function reject_sets_pending_to_false(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")->assertOk();

        $this->assertFalse((bool) $payment->fresh()->pending);
    }

    #[Test]
    public function reject_does_not_set_approved_at(): void
    {
        $finance = $this->financeUser();
        $payment = $this->pendingPayment();

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")->assertOk();

        $this->assertNull($payment->fresh()->approved_at);
    }

    // =========================================================================
    // 403 — employee cannot approve or reject
    // =========================================================================

    #[Test]
    public function employee_cannot_approve_a_payment(): void
    {
        $employee = $this->employeeUser();
        $payment  = $this->pendingPayment();

        Passport::actingAs($employee);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertForbidden();
    }

    #[Test]
    public function employee_cannot_reject_a_payment(): void
    {
        $employee = $this->employeeUser();
        $payment  = $this->pendingPayment();

        Passport::actingAs($employee);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertForbidden();
    }

    // =========================================================================
    // 401 — unauthenticated
    // =========================================================================

    #[Test]
    public function unauthenticated_user_cannot_approve(): void
    {
        $payment = $this->pendingPayment();

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertUnauthorized();
    }

    #[Test]
    public function unauthenticated_user_cannot_reject(): void
    {
        $payment = $this->pendingPayment();

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertUnauthorized();
    }

    // =========================================================================
    // 400 — non-pending payments
    // =========================================================================

    #[Test]
    public function cannot_approve_an_already_approved_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => now(),
            'approved_by' => $finance->id,
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be approved.');
    }

    #[Test]
    public function cannot_approve_a_rejected_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => null,
            'expired_at'  => null,
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be approved.');
    }

    #[Test]
    public function cannot_approve_an_expired_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'    => false,
            'expired_at' => now()->subDay(),
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/approve")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be approved.');
    }

    #[Test]
    public function cannot_reject_an_already_approved_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => now(),
            'approved_by' => $finance->id,
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be rejected.');
    }

    #[Test]
    public function cannot_reject_an_already_rejected_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'     => false,
            'approved_at' => null,
            'expired_at'  => null,
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be rejected.');
    }

    #[Test]
    public function cannot_reject_an_expired_payment(): void
    {
        $finance = $this->financeUser();
        $payment = Payment::factory()->create([
            'pending'    => false,
            'expired_at' => now()->subDay(),
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$payment->id}/reject")
            ->assertStatus(400)
            ->assertJsonPath('message', 'Only pending requests can be rejected.');
    }

    // =========================================================================
    // 404 — payment not found
    // =========================================================================

    #[Test]
    public function approve_returns_404_for_nonexistent_payment(): void
    {
        Passport::actingAs($this->financeUser());

        $this->patchJson('/api/payment-requests/999999/approve')
            ->assertNotFound();
    }

    #[Test]
    public function reject_returns_404_for_nonexistent_payment(): void
    {
        Passport::actingAs($this->financeUser());

        $this->patchJson('/api/payment-requests/999999/reject')
            ->assertNotFound();
    }
}

<?php

namespace Tests\Unit\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policy')]
class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PaymentPolicy;
    }

    #[Test]
    public function any_authenticated_user_can_view_the_list(): void
    {
        $employee = User::factory()->create(['department' => 'employee']);
        $finance = User::factory()->create(['department' => 'finance']);

        $this->assertTrue($this->policy->viewAny($employee));
        $this->assertTrue($this->policy->viewAny($finance));
    }

    #[Test]
    public function employee_can_view_their_own_payment(): void
    {
        $employee = User::factory()->create(['department' => 'employee']);
        $payment = Payment::factory()->create(['user_id' => $employee->id]);

        $this->assertTrue($this->policy->view($employee, $payment));
    }

    #[Test]
    public function employee_cannot_view_another_users_payment(): void
    {
        $employee = User::factory()->create(['department' => 'employee']);
        $other = User::factory()->create(['department' => 'employee']);
        $payment = Payment::factory()->create(['user_id' => $other->id]);

        $this->assertFalse($this->policy->view($employee, $payment));
    }

    #[Test]
    public function finance_can_view_any_payment(): void
    {
        $finance = User::factory()->create(['department' => 'finance']);
        $employee = User::factory()->create(['department' => 'employee']);
        $payment = Payment::factory()->create(['user_id' => $employee->id]);

        $this->assertTrue($this->policy->view($finance, $payment));
    }
}

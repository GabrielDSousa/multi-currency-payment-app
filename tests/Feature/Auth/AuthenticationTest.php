<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('auth')]
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('passport:client --personal --no-interaction');
    }

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $payload = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'BR',
            'currency_code' => 'BRL',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'country', 'currency_code'],
                'token' => ['access_token', 'token_type', 'scopes'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
    }

    #[Test]
    public function register_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    #[Test]
    public function register_fails_with_invalid_email(): void
    {
        $payload = [
            'name' => 'A',
            'email' => 'not-an-email',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'BR',
            'currency_code' => 'BRL'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function register_fails_with_weak_password(): void
    {
        $payload = [
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
            'country' => 'BR',
            'currency_code' => 'BRL'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $payload = [
            'name' => 'Dupe',
            'email' => 'dupe@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'BR',
            'currency_code' => 'BRL'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function register_fails_with_invalid_country_code(): void
    {
        $payload = [
            'name' => 'A',
            'email' => 'a2@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'XX',
            'currency_code' => 'BRL'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function register_fails_with_invalid_currency_code(): void
    {
        $payload = [
            'name' => 'A',
            'email' => 'a3@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'BR',
            'currency_code' => 'XXX'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function register_fails_with_invalid_department(): void
    {
        $payload = [
            'name' => 'A',
            'email' => 'a4@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'country' => 'BR',
            'currency_code' => 'BRL',
            'departament' => 'unknown'
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422);
    }

    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'login@example.com', 'password' => Hash::make('SenhaSegura123!')]);

        $response = $this->postJson('/api/login', ['email' => 'login@example.com', 'password' => 'SenhaSegura123!']);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email', 'country', 'currency_code'], 'token' => ['access_token', 'token_type', 'scopes']]);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'wp@example.com', 'password' => Hash::make('rightpass')]);

        $response = $this->postJson('/api/login', ['email' => 'wp@example.com', 'password' => 'wrongpass']);
        $response->assertStatus(401);
    }

    #[Test]
    public function login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'nope@example.com', 'password' => 'whatever']);
        $response->assertStatus(401);
    }

    #[Test]
    public function user_login_have_finance_scope_when_department_is_finance(): void
    {
        User::factory()->create(['email' => 'fin@example.com', 'password' => Hash::make('SenhaSegura123!'), 'department' => 'finance']);

        $response = $this->postJson('/api/login', ['email' => 'fin@example.com', 'password' => 'SenhaSegura123!', 'scope' => 'finance']);
        $response->assertStatus(200)
            ->assertJsonPath('token.scopes.0', 'finance');
    }

    #[Test]
    public function user_login_have_employee_scope_when_department_is_employee(): void
    {
        User::factory()->create(['email' => 'def@example.com', 'password' => Hash::make('SenhaSegura123!'), 'department' => 'employee']);

        $response = $this->postJson('/api/login', ['email' => 'def@example.com', 'password' => 'SenhaSegura123!']);
        $response->assertStatus(200)
            ->assertJsonPath('token.scopes.0', 'employee');
    }

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t', ['employee'])->accessToken;

        $response = $this->withHeader('Authorization', "Bearer $token")->postJson('/api/logout');
        $response->assertStatus(200)->assertJson(['message' => 'Logged out successfully.']);
    }

    #[Test]
    public function token_is_revoked_after_logout(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('t', ['employee']);
        $token = $tokenResult;

        $this->withHeader('Authorization', "Bearer $token->accessToken")->postJson('/api/logout')->assertStatus(200);

        $res = $this->withHeader('Authorization', "Bearer $token->accessToken")->postJson('/api/logout');
        $res->assertStatus(401);
    }

    #[Test]
    public function logout_fails_without_token(): void
    {
        $res = $this->postJson('/api/logout');
        $res->assertStatus(401);
    }
}

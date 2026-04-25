<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function withSpaHeaders(): self
    {
        return $this
            ->withHeader('Origin', 'http://localhost:5173')
            ->withHeader('Referer', 'http://localhost:5173/login');
    }

    public function test_it_logs_in_and_returns_user(): void
    {
        $password = 'secret1234';
        User::query()->create([
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => Hash::make($password),
        ]);

        $response = $this->withSpaHeaders()->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.email', 'login@example.com')
            ->assertJsonPath('data.session.isActive', true);
        $this->assertAuthenticated();
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        $password = 'secret1234';
        User::query()->create([
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => Hash::make($password),
        ]);

        $response = $this->withSpaHeaders()->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'These credentials do not match our records.');
        $this->assertGuest();
    }

    public function test_it_validates_input(): void
    {
        $response = $this->withSpaHeaders()->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_it_registers_user_and_stores_hashed_password(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('data.user.name', $payload['name'])
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.session.isActive', true);

        $user = User::query()->where('email', $payload['email'])->firstOrFail();

        $this->assertNotSame($payload['password'], $user->password);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
    }

    public function test_it_authenticates_session_when_registering_from_spa(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane-spa@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'secret123',
        ];

        $response = $this
            ->withHeader('Origin', 'http://localhost:5173')
            ->withHeader('Referer', 'http://localhost:5173/register')
            ->postJson('/api/register', $payload);

        $response->assertCreated();
        $this->assertAuthenticated();
    }

    public function test_it_requires_unique_email_and_matching_password_confirmation(): void
    {
        User::query()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'not-matching',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'passwordConfirmation']);
    }
}

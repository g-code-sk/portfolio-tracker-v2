<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_user_and_stores_hashed_password(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.email', $payload['email']);

        $user = User::query()->where('email', $payload['email'])->firstOrFail();

        $this->assertNotSame($payload['password'], $user->password);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
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
            'password_confirmation' => 'not-matching',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}

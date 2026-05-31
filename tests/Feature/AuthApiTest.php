<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@hotel.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@hotel.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.email', 'test@hotel.com');
    }

    public function test_login_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@hotel.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'test@hotel.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_login_validation_requires_email_password(): void
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422);
    }
}

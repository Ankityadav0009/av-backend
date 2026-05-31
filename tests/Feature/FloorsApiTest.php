<?php

namespace Tests\Feature;

use App\Models\Floor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloorsApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): self
    {
        $user = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($user, 'sanctum');
    }

    public function test_floors_index_requires_auth(): void
    {
        $response = $this->getJson('/api/floors');
        $response->assertStatus(401);
    }

    public function test_floors_index_returns_list(): void
    {
        Floor::create(['floor_number' => 1, 'floor_name' => 'Ground', 'description' => '', 'is_active' => true]);
        Floor::create(['floor_number' => 2, 'floor_name' => 'First', 'description' => '', 'is_active' => true]);

        $response = $this->actingAsAdmin()->getJson('/api/floors');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_floors_store(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/floors', [
            'floor_number' => 1,
            'floor_name' => 'Ground Floor',
            'description' => 'Ground',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('floor_name', 'Ground Floor');
        $this->assertDatabaseHas('floors', ['floor_name' => 'Ground Floor']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(401);
    }

    public function test_dashboard_returns_stats(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_rooms',
                'available_rooms',
                'occupied_rooms',
                'today_check_ins',
                'today_check_outs',
                'today_revenue',
                'monthly_revenue',
            ]);
    }
}

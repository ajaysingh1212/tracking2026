<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiagnosticEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_diagnostic_event_is_recorded(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/gps/diagnostics', [
            'event_type' => 'browser_hidden',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('diagnostic_logs', [
            'user_id' => $user->id,
            'event_type' => 'browser_hidden',
        ]);
    }

    public function test_a_non_browser_event_type_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/gps/diagnostics', [
            'event_type' => 'phone_restarted',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('event_type');
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->postJson('/api/v1/gps/diagnostics', ['event_type' => 'browser_hidden'])
            ->assertStatus(401);
    }
}

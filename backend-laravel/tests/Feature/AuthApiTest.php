<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_seeded_user_can_log_in(): void
    {
        $this->seed();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.username', 'admin')
            ->assertJsonPath('user.role', 'admin');
    }
}

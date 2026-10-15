<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_cannot_open_the_catalog_api(): void
    {
        $this->getJson('/api/catalog')->assertUnauthorized();
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->getJson('/api/me')->assertNoContent();
    }

    public function test_employees_can_log_in_and_out(): void
    {
        $this->postJson('/login', [
            'email' => 'aeris@claimly.test',
            'password' => 'salah',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/login', [
            'email' => config('claimly.employee_email'),
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Aeris S.')
            ->assertJsonPath('user.is_admin', true);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', config('claimly.employee_email'));

        $this->postJson('/logout')->assertOk();
        $this->getJson('/api/catalog')->assertUnauthorized();
    }

    public function test_employees_can_change_their_password(): void
    {
        $this->patchJson('/api/password', [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertUnauthorized();

        $this->postJson('/login', [
            'email' => 'dina@claimly.test',
            'password' => 'password',
        ])->assertOk();

        $this->patchJson('/api/password', [
            'current_password' => 'salah',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/password', [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'tidak-sama',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->patchJson('/api/password', [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertOk();

        $this->postJson('/logout')->assertOk();

        $this->postJson('/login', [
            'email' => 'dina@claimly.test',
            'password' => 'password',
        ])->assertUnprocessable();

        $this->postJson('/login', [
            'email' => 'dina@claimly.test',
            'password' => 'password-baru',
        ])->assertOk()->assertJsonPath('user.name', 'Dina K.');
    }

    public function test_non_admins_cannot_manage_the_catalog(): void
    {
        $employee = User::factory()->create(['is_admin' => false]);

        $this->actingAs($employee)
            ->postJson('/api/categories', [
                'brand_id' => 1,
                'name' => 'Baru',
            ])
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
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
            'email' => 'tidak-ada@claimly.test',
            'password' => 'password',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'Email tidak terdaftar.');

        $this->postJson('/login', [
            'email' => 'aeris@claimly.test',
            'password' => 'salah',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'Password salah.');

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

    public function test_registered_employees_can_sign_in_with_google(): void
    {
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get('/auth/google?redirect=/profile/riwayat');
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('prompt=select_account', $location);
        $this->assertSame('/profile/riwayat', session('url.intended'));

        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-aeris',
            'email' => 'Aeris@claimly.test',
            'name' => 'Nama Dari Google',
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect('/profile/riwayat');

        $user = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-aeris', $user->google_id);
        $this->assertSame('Aeris S.', $user->name);
    }

    public function test_unknown_google_accounts_are_not_created(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-stranger',
            'email' => 'stranger@gmail.com',
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect('/login?google=unregistered');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'stranger@gmail.com']);
    }

    public function test_google_login_can_be_limited_to_company_domains(): void
    {
        config(['services.google.allowed_domains' => 'aeris-fti.com']);

        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-aeris',
            'email' => config('claimly.employee_email'),
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect('/login?google=domain');

        $this->assertGuest();
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

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login berhasil dengan kredensial yang valid dan mendapatkan token.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@kost.com',
            'password' => Hash::make('secret123'),
            'role' => 'penghuni',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'budi@kost.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'room_id'],
            ]);
    }

    /**
     * Test login gagal dengan password salah mengembalikan status 401.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@kost.com',
            'password' => Hash::make('secret123'),
            'role' => 'penghuni',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'budi@kost.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Email atau password salah.',
            ]);
    }

    /**
     * Test validasi login gagal mengembalikan status 422.
     */
    public function test_login_validation_failure_returns_422(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test endpoint yang diproteksi mengembalikan status 401 saat token tidak ada.
     */
    public function test_protected_endpoints_require_token_and_return_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    /**
     * Test logout berhasil saat user telah diautentikasi dengan token.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@kost.com',
            'password' => Hash::make('secret123'),
            'role' => 'penghuni',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout berhasil',
            ]);

        $this->assertCount(0, $user->tokens);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $penghuni;
    private string $adminToken;
    private string $penghuniToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Kost',
            'email' => 'admin@kost.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $this->adminToken = $this->admin->createToken('admin_token')->plainTextToken;

        $this->penghuni = User::create([
            'name' => 'Penghuni Satu',
            'email' => 'penghuni@kost.com',
            'password' => Hash::make('password'),
            'role' => 'penghuni',
        ]);
        $this->penghuniToken = $this->penghuni->createToken('penghuni_token')->plainTextToken;
    }

    /**
     * Test admin dapat menambahkan kamar kos baru (CRUD - Create) dengan status 201.
     */
    public function test_admin_can_create_room_and_returns_201(): void
    {
        $payload = [
            'room_number' => 'A101',
            'type' => 'Standard',
            'price' => 1000000,
            'capacity' => 1,
            'facilities' => 'Kasur, Lemari, WiFi',
            'description' => 'Kamar standar lantai 1',
            'status' => 'available',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/rooms', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Kamar berhasil ditambahkan')
            ->assertJsonPath('data.room_number', 'A101');

        $this->assertDatabaseHas('rooms', [
            'room_number' => 'A101',
            'type' => 'Standard',
        ]);
    }

    /**
     * Test validasi saat tambah kamar gagal menghasilkan status 422.
     */
    public function test_create_room_validation_failure_returns_422(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/rooms', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_number', 'type', 'price', 'capacity', 'facilities']);
    }

    /**
     * Test penghuni tidak dapat menambahkan kamar (Role check returns 403).
     */
    public function test_penghuni_cannot_create_room_returns_403(): void
    {
        $payload = [
            'room_number' => 'B202',
            'type' => 'Deluxe',
            'price' => 1500000,
            'capacity' => 1,
            'facilities' => 'AC, WiFi',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->postJson('/api/rooms', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test user terautentikasi dapat melihat daftar kamar (CRUD - Read All).
     */
    public function test_authenticated_user_can_view_all_rooms(): void
    {
        Room::create([
            'room_number' => 'K01',
            'type' => 'Single',
            'price' => 900000,
            'capacity' => 1,
            'facilities' => 'Kasur, Kipas',
            'status' => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->getJson('/api/rooms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'room_number', 'type', 'price', 'capacity', 'facilities', 'status'],
                ],
            ]);
    }

    /**
     * Test user terautentikasi dapat melihat detail 1 kamar (CRUD - Read One).
     */
    public function test_authenticated_user_can_view_single_room(): void
    {
        $room = Room::create([
            'room_number' => 'K02',
            'type' => 'VIP',
            'price' => 2000000,
            'capacity' => 2,
            'facilities' => 'AC, Smart TV, Kamar Mandi Dalam',
            'status' => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->getJson('/api/rooms/' . $room->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.room_number', 'K02')
            ->assertJsonPath('data.type', 'VIP');
    }

    /**
     * Test admin dapat mengubah data kamar kos (CRUD - Update).
     */
    public function test_admin_can_update_room(): void
    {
        $room = Room::create([
            'room_number' => 'K03',
            'type' => 'Standard',
            'price' => 1000000,
            'capacity' => 1,
            'facilities' => 'Kipas',
            'status' => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/rooms/' . $room->id, [
                'type' => 'Standard Plus',
                'price' => 1100000,
                'facilities' => 'Kipas, Meja Belajar',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kamar berhasil diperbarui')
            ->assertJsonPath('data.type', 'Standard Plus')
            ->assertJsonPath('data.price', 1100000);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'type' => 'Standard Plus',
        ]);
    }

    /**
     * Test admin dapat menghapus kamar kos (CRUD - Delete).
     */
    public function test_admin_can_delete_room(): void
    {
        $room = Room::create([
            'room_number' => 'K04',
            'type' => 'Standard',
            'price' => 1000000,
            'capacity' => 1,
            'facilities' => 'Kipas',
            'status' => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/api/rooms/' . $room->id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kamar berhasil dihapus');

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    /**
     * Test alur penghuni: booking kamar (201), pantau fasilitas, dan checkout.
     */
    public function test_penghuni_can_book_monitor_facilities_and_checkout(): void
    {
        $room = Room::create([
            'room_number' => 'K05',
            'type' => 'Deluxe',
            'price' => 1500000,
            'capacity' => 1,
            'facilities' => 'AC, WiFi, Water Heater',
            'status' => 'available',
        ]);

        // 1. Booking kamar -> status 201 Created
        $bookResponse = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->postJson("/api/rooms/{$room->id}/book");

        $bookResponse->assertStatus(201)
            ->assertJsonPath('message', 'Berhasil melakukan booking kamar K05');

        $this->assertDatabaseHas('users', [
            'id' => $this->penghuni->id,
            'room_id' => $room->id,
        ]);

        // 2. Memantau fasilitas & kamar yang ditempati
        $myRoomResponse = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->getJson('/api/my-room');

        $myRoomResponse->assertStatus(200)
            ->assertJsonPath('data.room.room_number', 'K05')
            ->assertJsonPath('data.facilities', 'AC, WiFi, Water Heater');

        // 3. Checkout dari kamar
        $checkoutResponse = $this->withHeader('Authorization', 'Bearer ' . $this->penghuniToken)
            ->postJson('/api/checkout');

        $checkoutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Berhasil checkout dari kamar kos.');

        $this->assertDatabaseHas('users', [
            'id' => $this->penghuni->id,
            'room_id' => null,
        ]);
    }
}

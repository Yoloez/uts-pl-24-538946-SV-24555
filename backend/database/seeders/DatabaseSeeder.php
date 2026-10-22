<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@kost.com'],
            [
                'name' => 'Administrator Kost',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 2. Akun Penghuni Contoh
        $penghuni = User::firstOrCreate(
            ['email' => 'penghuni@kost.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'penghuni',
            ]
        );

        // 3. Data Kamar Kos Awal
        $rooms = [
            [
                'room_number' => 'A101',
                'type' => 'Standard Single',
                'price' => 1200000,
                'capacity' => 1,
                'facilities' => 'Kasur Single, Lemari Baju, Meja Belajar, Kipas Angin, Kamar Mandi Luar, WiFi',
                'description' => 'Kamar tipe standar di lantai 1 dengan pencahayaan alami dan sirkulasi udara baik.',
                'status' => 'available',
            ],
            [
                'room_number' => 'B201',
                'type' => 'Deluxe Room',
                'price' => 1800000,
                'capacity' => 1,
                'facilities' => 'AC, Kamar Mandi Dalam, Kasur Queen, Water Heater, Lemari 2 Pintu, Meja Kerja, WiFi',
                'description' => 'Kamar deluxe di lantai 2 dengan fasilitas lengkap dan kamar mandi dalam.',
                'status' => 'available',
            ],
            [
                'room_number' => 'C301',
                'type' => 'VIP Suite',
                'price' => 2500000,
                'capacity' => 2,
                'facilities' => 'AC, Smart TV, Kamar Mandi Dalam (Bathtub), Kulkas Mini, Balkon Pribadi, WiFi High Speed',
                'description' => 'Kamar tipe suite di lantai 3 berkapasitas 2 orang dengan pemandangan kota dan balkon.',
                'status' => 'available',
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::firstOrCreate(['room_number' => $roomData['room_number']], $roomData);
        }
    }
}

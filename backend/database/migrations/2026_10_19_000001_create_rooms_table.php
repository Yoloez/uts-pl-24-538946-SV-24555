<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('type'); // e.g. Single, Deluxe, VIP
            $table->decimal('price', 12, 2);
            $table->integer('capacity')->default(1);
            $table->text('facilities'); // list of facilities, e.g. AC, WiFi, Kasur, Kamar Mandi Dalam
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'full', 'maintenance'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

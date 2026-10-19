<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'type',
        'price',
        'capacity',
        'facilities',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the users currently occupying this room.
     */
    public function occupants(): HasMany
    {
        return $this->hasMany(User::class, 'room_id');
    }

    /**
     * Get all booking records for this room.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

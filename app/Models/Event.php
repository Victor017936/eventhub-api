<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property int $created_by
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $location
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $booking_starts_at
 * @property Carbon|null $booking_ends_at
 * @property int $capacity
 * @property EventStatus $status
 * @property-read Category $category
 * @property-read User $creator
 * @property-read Collection<int, Reservation> $reservations
 */
#[Fillable([
    'category_id',
    'created_by',
    'title',
    'slug',
    'description',
    'location',
    'starts_at',
    'ends_at',
    'booking_starts_at',
    'booking_ends_at',
    'capacity',
    'status',
])]

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'booking_starts_at' => 'datetime',
            'booking_ends_at' => 'datetime',
            'capacity' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}

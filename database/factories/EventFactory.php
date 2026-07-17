<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');
        $endsAt = (clone $startsAt)->modify('+2 hours');
        $bookingEndsAt = (clone $startsAt)->modify('-1 day');

        return [
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'booking_starts_at' => now(),
            'booking_ends_at' => $bookingEndsAt,
            'capacity' => fake()->numberBetween(10, 500),
            'status' => EventStatus::Draft,
        ];
    }
}

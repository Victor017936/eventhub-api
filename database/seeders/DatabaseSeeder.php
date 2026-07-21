<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            [
                'email' => 'admin@eventhub.test',
            ],
            [
                'name' => 'EventHub Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        $user = User::query()->updateOrCreate(
            [
                'email' => 'user@eventhub.test',
            ],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        );

        $cancelledUser = User::query()->updateOrCreate(
            [
                'email' => 'cancelled@eventhub.test',
            ],
            [
                'name' => 'Cancelled Reservation User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        );

        $technology = Category::query()->updateOrCreate(
            [
                'slug' => 'technology',
            ],
            [
                'name' => 'Tehnologie',
                'description' => 'Evenimente despre programare și tehnologie.',
                'is_active' => true,
            ],
        );

        $business = Category::query()->updateOrCreate(
            [
                'slug' => 'business',
            ],
            [
                'name' => 'Business',
                'description' => 'Evenimente despre antreprenoriat și management.',
                'is_active' => true,
            ],
        );

        $education = Category::query()->updateOrCreate(
            [
                'slug' => 'education',
            ],
            [
                'name' => 'Educație',
                'description' => 'Cursuri, workshopuri și evenimente educaționale.',
                'is_active' => true,
            ],
        );

        $conferenceStartsAt = now()->addDays(14)->setTime(10, 0);

        $conference = Event::query()->updateOrCreate(
            [
                'slug' => 'laravel-vue-conference',
            ],
            [
                'category_id' => $technology->id,
                'created_by' => $admin->id,
                'title' => 'Laravel & Vue Conference',
                'description' => 'O conferință dedicată dezvoltării aplicațiilor moderne cu Laravel și Vue.',
                'location' => 'Chișinău',
                'starts_at' => $conferenceStartsAt,
                'ends_at' => $conferenceStartsAt->copy()->addHours(8),
                'booking_starts_at' => now()->subDay(),
                'booking_ends_at' => $conferenceStartsAt->copy()->subDay(),
                'capacity' => 100,
                'status' => EventStatus::Published,
            ],
        );

        $workshopStartsAt = now()->addDays(30)->setTime(9, 30);

        Event::query()->updateOrCreate(
            [
                'slug' => 'project-management-workshop',
            ],
            [
                'category_id' => $business->id,
                'created_by' => $admin->id,
                'title' => 'Project Management Workshop',
                'description' => 'Workshop practic despre planificarea și coordonarea proiectelor IT.',
                'location' => 'Chișinău',
                'starts_at' => $workshopStartsAt,
                'ends_at' => $workshopStartsAt->copy()->addHours(5),
                'booking_starts_at' => now()->subDay(),
                'booking_ends_at' => $workshopStartsAt->copy()->subDay(),
                'capacity' => 50,
                'status' => EventStatus::Published,
            ],
        );

        $courseStartsAt = now()->addDays(45)->setTime(18, 0);

        Event::query()->updateOrCreate(
            [
                'slug' => 'software-testing-course',
            ],
            [
                'category_id' => $education->id,
                'created_by' => $admin->id,
                'title' => 'Software Testing Course',
                'description' => 'Curs introductiv despre testarea aplicațiilor software.',
                'location' => 'Online',
                'starts_at' => $courseStartsAt,
                'ends_at' => $courseStartsAt->copy()->addHours(3),
                'booking_starts_at' => null,
                'booking_ends_at' => null,
                'capacity' => 75,
                'status' => EventStatus::Draft,
            ],
        );

        Reservation::query()->updateOrCreate(
            [
                'event_id' => $conference->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'confirmed',
                'cancelled_at' => null,
            ],
        );

        Reservation::query()->updateOrCreate(
            [
                'event_id' => $conference->id,
                'user_id' => $cancelledUser->id,
            ],
            [
                'status' => 'cancelled',
                'cancelled_at' => now()->subDay(),
            ],
        );
    }
}

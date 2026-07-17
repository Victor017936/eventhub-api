<?php

namespace App\Jobs;

use App\Mail\ReservationConfirmed;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendReservationConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function handle(): void
    {
        $this->reservation->loadMissing([
            'user',
            'event',
        ]);

        Mail::to($this->reservation->user->email)
            ->send(
                new ReservationConfirmed($this->reservation)
            );
    }
}

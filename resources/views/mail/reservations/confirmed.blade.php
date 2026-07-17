<x-mail::message>
# Reservation confirmed

Hello {{ $reservation->user->name }},

Your reservation for **{{ $reservation->event->title }}** has been confirmed.

**Date:** {{ $reservation->event->starts_at->format('d.m.Y H:i') }}

@if ($reservation->event->location)
**Location:** {{ $reservation->event->location }}
@endif

**Reservation ID:** {{ $reservation->id }}

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>
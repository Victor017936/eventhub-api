<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Console\Command;

class CompletePastEvents extends Command
{
    /**
     * @var string
     */
    protected $signature = 'events:complete';

    /**
     * @var string
     */
    protected $description = 'Mark past published events as completed';

    public function handle(): int
    {
        $completedEventsCount = Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('ends_at', '<=', now())
            ->update([
                'status' => EventStatus::Completed->value,
            ]);

        $this->info(
            "Completed {$completedEventsCount} event(s)."
        );

        return self::SUCCESS;
    }
}

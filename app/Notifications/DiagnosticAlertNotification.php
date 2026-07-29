<?php

namespace App\Notifications;

use App\Models\DiagnosticLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DiagnosticAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DiagnosticLog $log,
        public readonly User $trackedUser,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'diagnostic_alert',
            'category' => 'diagnostic',
            'message' => "{$this->trackedUser->name}: {$this->log->event_type->label()}",
            'action_url' => route('live-map.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}

<?php

namespace App\Jobs;

use App\Contracts\EmailNotificationServiceInterface;
use App\Models\EmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $notificationId)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(EmailNotificationServiceInterface $emailNotificationService): void
    {
        $notification = EmailNotification::find($this->notificationId);

        if (!$notification || in_array($notification->status, ['delivered', 'sent'], true)) {
            return;
        }

        $emailNotificationService->sendNotification($notification);
    }
}

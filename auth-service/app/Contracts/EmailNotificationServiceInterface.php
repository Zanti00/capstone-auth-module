<?php

namespace App\Contracts;

use App\Models\EmailNotification;

interface EmailNotificationServiceInterface
{
    public function queueNotification(
        string $templateKey,
        string $recipientEmail,
        array $params,
        ?int $userId = null,
        ?string $subject = null
    ): EmailNotification;

    public function sendNotification(EmailNotification $notification): void;
}

<?php

namespace App\Services\Email;

use App\Contracts\EmailNotificationServiceInterface;
use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailNotification;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoEmailNotificationService implements EmailNotificationServiceInterface
{
    public function __construct(private readonly EmailTemplateRegistry $templateRegistry)
    {
    }

    public function queueNotification(
        string $templateKey,
        string $recipientEmail,
        array $params,
        ?int $userId = null,
        ?string $subject = null
    ): EmailNotification {
        $notification = EmailNotification::create([
            'user_id' => $userId,
            'notification_type' => $templateKey,
            'provider' => 'brevo',
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'template_key' => $templateKey,
            'template_id' => $this->templateRegistry->getProviderTemplateId($templateKey),
            'status' => 'pending',
            'payload' => $params,
        ]);

        SendTransactionalEmailJob::dispatch($notification->id);

        return $notification;
    }

    public function sendNotification(EmailNotification $notification): void
    {
        $response = Http::baseUrl((string) config('services.brevo.base_url'))
            ->acceptJson()
            ->withHeaders([
                'api-key' => (string) config('services.brevo.api_key'),
            ])
            ->post('/smtp/email', [
                'to' => [
                    [
                        'email' => $notification->recipient_email,
                    ],
                ],
                'templateId' => (int) $notification->template_id,
                'params' => $notification->payload,
                'subject' => $notification->subject,
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            Log::warning('Brevo transactional email send failed.', [
                'notification_id' => $notification->id,
                'recipient_email' => $notification->recipient_email,
                'response' => $response->json(),
            ]);

            throw $exception;
        }

        $messageId = $response->json('messageId') ?? $response->json('message-id');

        $notification->events()->create([
            'provider' => 'brevo',
            'event_type' => config('email_notifications.webhook_events.request'),
            'provider_message_id' => $messageId,
            'payload' => $response->json(),
            'occurred_at' => now(),
        ]);

        $notification->update([
            'provider_message_id' => $messageId,
            'status' => 'sent',
            'error_message' => null,
            'failed_at' => null,
            'last_sent_at' => now(),
        ]);
    }
}

<?php

namespace App\Services\Email;

use App\Models\EmailNotification;
use App\Models\EmailNotificationEvent;
use Illuminate\Support\Arr;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class EmailWebhookService
{
    public function handleBrevoWebhook(string $rawPayload, array $headers, array $payload): void
    {
        $this->assertValidSignature($rawPayload, $headers);

        $eventType = (string) ($payload['event'] ?? 'unknown');
        $messageId = (string) ($payload['message-id'] ?? $payload['messageId'] ?? '');
        $eventId = (string) ($payload['uuid'] ?? $payload['event_id'] ?? '');
        $occurredAt = $payload['date'] ?? now();

        $notification = EmailNotification::query()
            ->when($messageId !== '', fn ($query) => $query->where('provider_message_id', $messageId))
            ->latest('id')
            ->first();

        if ($eventId !== '' && EmailNotificationEvent::where('provider_event_id', $eventId)->exists()) {
            return;
        }

        EmailNotificationEvent::create([
            'email_notification_id' => $notification?->id,
            'provider' => 'brevo',
            'event_type' => $eventType,
            'provider_event_id' => $eventId !== '' ? $eventId : null,
            'provider_message_id' => $messageId !== '' ? $messageId : null,
            'payload' => $payload,
            'occurred_at' => $occurredAt,
        ]);

        if (!$notification) {
            Log::warning('Brevo webhook event could not be matched to a notification.', [
                'message_id' => $messageId,
                'event_type' => $eventType,
            ]);

            return;
        }

        $notification->update([
            'status' => $this->mapStatus($eventType),
            'error_message' => Arr::get($payload, 'reason'),
            'failed_at' => in_array($eventType, ['hardBounce', 'softBounce', 'blocked', 'error', 'invalid', 'spam'], true) ? now() : null,
        ]);
    }

    private function assertValidSignature(string $rawPayload, array $headers): void
    {
        $secret = (string) config('services.brevo.webhook_secret');

        if ($secret === '') {
            return;
        }

        $headerName = strtolower((string) config('services.brevo.webhook_secret_header', 'X-Brevo-Webhook-Secret'));
        $headerValues = $headers[$headerName] ?? [];
        $providedSecret = is_array($headerValues) ? (string) ($headerValues[0] ?? '') : (string) $headerValues;

        if (!hash_equals($secret, $providedSecret)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Invalid webhook secret header.',
                ], 403)
            );
        }
    }

    private function mapStatus(string $eventType): string
    {
        return match ($eventType) {
            'delivered' => 'delivered',
            'sent', 'request', 'deferred' => 'sent',
            'hardBounce', 'softBounce', 'blocked', 'error', 'invalid', 'spam' => 'failed',
            default => 'sent',
        };
    }
}

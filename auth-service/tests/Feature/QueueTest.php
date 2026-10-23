<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that transactional email jobs can be executed successfully.
     */
    public function test_transactional_email_job_executes_successfully()
    {
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response([
                'messageId' => 'queue-test-message',
            ], 201),
        ]);

        $notification = EmailNotification::create([
            'notification_type' => 'welcome_temp_password',
            'provider' => 'brevo',
            'recipient_email' => 'queue-test@example.com',
            'subject' => 'Welcome',
            'template_key' => 'welcome_temp_password',
            'template_id' => '101',
            'status' => 'pending',
            'payload' => [
                'temporary_password' => 'TempPass123!',
            ],
        ]);

        dispatch_sync(new SendTransactionalEmailJob($notification->id));

        $this->assertDatabaseHas('email_notifications', [
            'id' => $notification->id,
            'status' => 'sent',
            'provider_message_id' => 'queue-test-message',
        ]);
    }
}

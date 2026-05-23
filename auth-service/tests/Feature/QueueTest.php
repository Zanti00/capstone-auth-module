<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QueueTest extends TestCase
{
    /**
     * Test that the WelcomeEmail is correctly queued when sent.
     */
    public function test_welcome_email_is_correctly_queued()
    {
        Mail::fake();

        $email = 'queue-test-' . time() . '@sbsi.com';
        $password = 'TempPass123!';

        // Dispatch WelcomeEmail
        Mail::to($email)->send(new WelcomeEmail($email, $password));

        // Assert that the WelcomeEmail was queued to the target email address
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }
}

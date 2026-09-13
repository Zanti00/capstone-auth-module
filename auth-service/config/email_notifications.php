<?php

return [
    'provider' => env('EMAIL_NOTIFICATIONS_PROVIDER', 'brevo'),

    'templates' => [
        'welcome_temp_password' => 'welcome_temp_password',
        'password_reset' => 'password_reset',
        'email_verification' => 'email_verification',
        'password_changed_confirmation' => 'password_changed_confirmation',
    ],

    'webhook_events' => [
        'request' => 'request',
        'sent' => 'sent',
        'delivered' => 'delivered',
        'deferred' => 'deferred',
        'soft_bounce' => 'soft_bounce',
        'hard_bounce' => 'hard_bounce',
        'blocked' => 'blocked',
        'error' => 'error',
        'invalid' => 'invalid',
    ],
];

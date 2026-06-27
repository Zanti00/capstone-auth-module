<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.templates.welcome_temp_password' => '101',
            'services.brevo.templates.password_reset' => '102',
            'services.brevo.templates.email_verification' => '103',
            'services.brevo.templates.password_changed_confirmation' => '104',
            'queue.default' => 'database',
        ]);
        
        if (\Illuminate\Support\Facades\Schema::hasTable('oauth_clients')) {
            \Illuminate\Support\Facades\Artisan::call('passport:client', [
                '--personal' => true,
                '--name' => 'Test Personal Access Client',
                '--no-interaction' => true,
            ]);
        }
    }
}

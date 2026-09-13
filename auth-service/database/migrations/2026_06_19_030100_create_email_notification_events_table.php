<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_notification_id')->nullable()->constrained('email_notifications')->nullOnDelete();
            $table->string('provider', 50);
            $table->string('event_type', 100);
            $table->string('provider_event_id')->nullable()->unique();
            $table->string('provider_message_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notification_events');
    }
};

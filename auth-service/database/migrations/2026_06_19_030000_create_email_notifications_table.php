<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification_type', 100);
            $table->string('provider', 50);
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('template_key', 100);
            $table->string('template_id', 100)->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('status', 50)->default('pending');
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['notification_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
    }
};

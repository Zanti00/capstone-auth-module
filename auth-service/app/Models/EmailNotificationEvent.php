<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotificationEvent extends Model
{
    protected $fillable = [
        'email_notification_id',
        'provider',
        'event_type',
        'provider_event_id',
        'provider_message_id',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function notification()
    {
        return $this->belongsTo(EmailNotification::class, 'email_notification_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotification extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'provider',
        'recipient_email',
        'subject',
        'template_key',
        'template_id',
        'provider_message_id',
        'status',
        'payload',
        'error_message',
        'last_sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(EmailNotificationEvent::class);
    }
}

<?php

namespace App\Notifications;

use App\Filament\Company\Resources\Sales\AccountMessageResource;
use App\Models\Common\AccountMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(protected AccountMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New message received',
            'body' => $this->message->subject,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender?->name,
            'url' => AccountMessageResource::getUrl('view', ['record' => $this->message]),
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PushBroadcastNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;

    public function __construct($title, $message, $url = '/admin')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon(secure_asset('images/logo_BG.png'))
            ->body($this->message)
            ->action('Buka Sekarang', 'open_url')
            ->data(['action_url' => $this->url])
            ->badge(secure_asset('images/logo_BG.png'))
            ->vibrate([200, 100, 210, 100, 200]);
    }
}

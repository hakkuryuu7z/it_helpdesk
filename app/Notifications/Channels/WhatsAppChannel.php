<?php

namespace App\Notifications\Channels;

use App\Services\WhatsAppGatewayService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(
        protected WhatsAppGatewayService $gateway
    ) {}

    /**
     * Send the given notification over WhatsApp.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!config('services.whatsapp.enabled', true)) {
            return;
        }

        $phone = method_exists($notifiable, 'routeNotificationForWhatsapp')
            ? $notifiable->routeNotificationForWhatsapp()
            : ($notifiable->phone ?? null);

        if (empty($phone)) {
            return;
        }

        if (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);
            if (!empty($message)) {
                $ticket = property_exists($notification, 'ticket') ? $notification->ticket : null;
                $recipientName = property_exists($notifiable, 'name') ? $notifiable->name : null;
                $this->gateway->send($phone, $message, $ticket, $recipientName);
            }
        }
    }
}

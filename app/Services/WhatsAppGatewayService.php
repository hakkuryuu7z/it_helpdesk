<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    /**
     * Dispatch WhatsApp message to the specified phone number.
     */
    public function send(string $phone, string $message): bool
    {
        if (!config('services.whatsapp.enabled', true)) {
            Log::info("[WHATSAPP DISABLED] Message to {$phone} skipped.");
            return false;
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $driver = config('services.whatsapp.driver', 'baileys');

        return match ($driver) {
            'baileys' => $this->sendViaBaileys($normalizedPhone, $message),
            'generic_webhook' => $this->sendViaGenericWebhook($normalizedPhone, $message),
            default => $this->sendViaLog($normalizedPhone, $message),
        };
    }

    /**
     * Normalize Indonesian and international phone numbers to standard numeric international format.
     * e.g. 0812-3456-7890 -> 6281234567890, +62 811... -> 62811...
     */
    public function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Check if the Baileys gateway service is online and linked.
     */
    public function isGatewayConnected(): bool
    {
        try {
            $baileysUrl = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3000');
            $response = Http::timeout(3)->get("{$baileysUrl}/status");

            if ($response->successful()) {
                return (bool) $response->json('connected', false);
            }
        } catch (\Throwable $e) {
            Log::warning("[WHATSAPP GATEWAY] Healthcheck failed: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Dispatch via local or containerized Baileys WhatsApp microservice.
     */
    protected function sendViaBaileys(string $phone, string $message): bool
    {
        try {
            $baileysUrl = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3000');

            $response = Http::timeout(10)->post("{$baileysUrl}/send-message", [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful() && $response->json('success')) {
                Log::info("[WHATSAPP BAILEYS SENT] Delivered to {$phone}");
                return true;
            }

            Log::error("[WHATSAPP BAILEYS FAILED] Destination: {$phone}. Response: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("[WHATSAPP BAILEYS EXCEPTION] Could not communicate with Baileys microservice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dispatch via generic HTTP webhook.
     */
    protected function sendViaGenericWebhook(string $phone, string $message): bool
    {
        try {
            $endpoint = config('services.whatsapp.endpoint');
            if (!$endpoint) {
                return false;
            }

            $response = Http::timeout(10)->post($endpoint, [
                'target' => $phone,
                'phone' => $phone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[WHATSAPP WEBHOOK FAILED] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log driver for development and test execution.
     */
    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("[WHATSAPP LOG MOCK]\nTo: {$phone}\nMessage:\n{$message}");
        return true;
    }
}

<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    /**
     * Dispatch WhatsApp message to the specified phone number and record in audit log.
     */
    public function send(string $phone, string $message, ?Ticket $ticket = null, ?string $recipientName = null): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (!config('services.whatsapp.enabled', true)) {
            Log::info("[WHATSAPP DISABLED] Message to {$phone} skipped.");
            $this->recordLog($normalizedPhone, $message, 'failed', 'WhatsApp notifications are globally disabled in config.', null, $ticket, $recipientName);
            return false;
        }

        $driver = config('services.whatsapp.driver', 'baileys');

        $result = match ($driver) {
            'baileys' => $this->sendViaBaileys($normalizedPhone, $message, $ticket, $recipientName),
            'generic_webhook' => $this->sendViaGenericWebhook($normalizedPhone, $message, $ticket, $recipientName),
            default => $this->sendViaLog($normalizedPhone, $message, $ticket, $recipientName),
        };

        return $result;
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
        $status = $this->getGatewayLiveStatus();
        return $status['connected'] ?? false;
    }

    /**
     * Fetch complete live gateway status and pending QR data from Baileys microservice.
     *
     * @return array{online: bool, connected: bool, user: ?string, has_qr_pending: bool, qr_data_url: ?string, error: ?string}
     */
    public function getGatewayLiveStatus(): array
    {
        $baileysUrl = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3001');

        try {
            $response = Http::timeout(3)->get("{$baileysUrl}/qr-data");

            if ($response->successful()) {
                return [
                    'online' => true,
                    'connected' => (bool) $response->json('connected', false),
                    'user' => $response->json('user'),
                    'has_qr_pending' => (bool) $response->json('has_qr_pending', false),
                    'qr_data_url' => $response->json('qr_data_url'),
                    'error' => null,
                ];
            }

            return [
                'online' => false,
                'connected' => false,
                'user' => null,
                'has_qr_pending' => false,
                'qr_data_url' => null,
                'error' => "HTTP {$response->status()}: " . $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'online' => false,
                'connected' => false,
                'user' => null,
                'has_qr_pending' => false,
                'qr_data_url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect/logout current WhatsApp session on Baileys microservice.
     */
    public function disconnectGateway(): array
    {
        $baileysUrl = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3001');

        try {
            $response = Http::timeout(5)->post("{$baileysUrl}/disconnect");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message', 'Sesi WhatsApp berhasil diputus.'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Gagal memutus sesi WhatsApp.'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch via local or containerized Baileys WhatsApp microservice.
     */
    protected function sendViaBaileys(string $phone, string $message, ?Ticket $ticket = null, ?string $recipientName = null): bool
    {
        $baileysUrl = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3001');

        try {
            $response = Http::timeout(10)->post("{$baileysUrl}/send-message", [
                'phone' => $phone,
                'message' => $message,
            ]);

            $payload = $response->json();

            if ($response->successful() && ($payload['success'] ?? false)) {
                Log::info("[WHATSAPP BAILEYS SENT] Delivered to {$phone}");
                $this->recordLog($phone, $message, 'sent', null, $payload, $ticket, $recipientName);
                return true;
            }

            $errMsg = $payload['error'] ?? $response->body();
            Log::error("[WHATSAPP BAILEYS FAILED] Destination: {$phone}. Response: " . $errMsg);
            $this->recordLog($phone, $message, 'failed', $errMsg, $payload, $ticket, $recipientName);
            return false;
        } catch (\Throwable $e) {
            Log::error("[WHATSAPP BAILEYS EXCEPTION] Could not communicate with Baileys microservice: " . $e->getMessage());
            $this->recordLog($phone, $message, 'failed', $e->getMessage(), null, $ticket, $recipientName);
            return false;
        }
    }

    /**
     * Dispatch via generic HTTP webhook.
     */
    protected function sendViaGenericWebhook(string $phone, string $message, ?Ticket $ticket = null, ?string $recipientName = null): bool
    {
        try {
            $endpoint = config('services.whatsapp.endpoint');
            if (!$endpoint) {
                $this->recordLog($phone, $message, 'failed', 'Generic webhook endpoint not configured.', null, $ticket, $recipientName);
                return false;
            }

            $response = Http::timeout(10)->post($endpoint, [
                'target' => $phone,
                'phone' => $phone,
                'message' => $message,
            ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $errMsg = $response->successful() ? null : $response->body();
            $this->recordLog($phone, $message, $status, $errMsg, $response->json(), $ticket, $recipientName);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[WHATSAPP WEBHOOK FAILED] " . $e->getMessage());
            $this->recordLog($phone, $message, 'failed', $e->getMessage(), null, $ticket, $recipientName);
            return false;
        }
    }

    /**
     * Log driver for development and test execution.
     */
    protected function sendViaLog(string $phone, string $message, ?Ticket $ticket = null, ?string $recipientName = null): bool
    {
        Log::info("[WHATSAPP LOG MOCK]\nTo: {$phone}\nMessage:\n{$message}");
        $this->recordLog($phone, $message, 'sent', null, ['mock' => true], $ticket, $recipientName);
        return true;
    }

    /**
     * Record notification in persistent database log table.
     */
    protected function recordLog(
        string $phone,
        string $message,
        string $status,
        ?string $errorMessage = null,
        ?array $responsePayload = null,
        ?Ticket $ticket = null,
        ?string $recipientName = null
    ): void {
        try {
            WhatsAppLog::create([
                'ticket_id' => $ticket?->id,
                'recipient_phone' => $phone,
                'recipient_name' => $recipientName,
                'message' => $message,
                'status' => $status,
                'error_message' => $errorMessage,
                'response_payload' => $responsePayload,
            ]);
        } catch (\Throwable $e) {
            Log::error("[WHATSAPP LOG RECORD ERROR] " . $e->getMessage());
        }
    }
}

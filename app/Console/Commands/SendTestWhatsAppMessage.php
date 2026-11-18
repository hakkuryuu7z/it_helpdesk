<?php

namespace App\Console\Commands;

use App\Services\WhatsAppGatewayService;
use Illuminate\Console\Command;

class SendTestWhatsAppMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'helpdesk:test-wa {phone : Destination WhatsApp number (e.g. 081234567890)} {--message= : Custom test message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test WhatsApp notification to verify Baileys gateway connection';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppGatewayService $gateway): int
    {
        $rawPhone = $this->argument('phone');
        $normalizedPhone = $gateway->normalizePhone($rawPhone);
        $driver = config('services.whatsapp.driver', 'baileys');

        $this->info("==================================================");
        $this->info(" Centralized IT Helpdesk: WhatsApp Gateway Tester");
        $this->info("==================================================");
        $this->line("Target Phone:   <comment>{$rawPhone}</comment> (Normalized: <comment>{$normalizedPhone}</comment>)");
        $this->line("Active Driver:  <comment>{$driver}</comment>");

        if ($driver === 'baileys') {
            $url = config('services.whatsapp.baileys_url', 'http://127.0.0.1:3000');
            $this->line("Baileys URL:    <comment>{$url}</comment>");
            $this->line("Checking gateway health...");

            if ($gateway->isGatewayConnected()) {
                $this->info("✅ Baileys microservice is CONNECTED to WhatsApp Web!");
            } else {
                $this->warn("⚠️  Baileys microservice is NOT connected or waiting for QR scan.");
                $this->line("👉 Please open {$url}/qr in your browser and scan the QR code with WhatsApp.");
            }
        }

        $message = $this->option('message') ?: "🔔 *IT HELPDESK TEST ALERT*\n\n"
            . "Halo! Ini adalah pesan uji coba dari sistem *Centralized IT Helpdesk*.\n"
            . "Notifikasi WhatsApp otomatis berhasil terkonfigurasi pada " . now()->format('d M Y, H:i:s') . " WIB.\n\n"
            . "_Centralized IT Helpdesk System_";

        $this->line("\nDispatching message...");

        $success = $gateway->send($normalizedPhone, $message, null, 'CLI Tester');

        if ($success) {
            $this->info("\n✅ Test WhatsApp message dispatched successfully!");
            return Command::SUCCESS;
        }

        $this->error("\n❌ Failed to dispatch WhatsApp message. Check storage/logs/laravel.log or Baileys console.");
        return Command::FAILURE;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('recipient_phone', 30);
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('sent'); // sent, failed
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('recipient_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};

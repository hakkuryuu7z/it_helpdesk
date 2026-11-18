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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('category_id')->constrained('ticket_categories')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            $table->string('type', 30)->index(); // incident, service_request, feature_request
            $table->string('subject', 255);
            $table->text('description');

            $table->string('priority', 20)->default('medium'); // low, medium, high, critical
            $table->string('impact', 20)->default('low'); // low, medium, high
            $table->string('urgency', 20)->default('low'); // low, medium, high
            $table->string('status', 30)->default('open')->index(); // open, under_review, in_progress, waiting_user, resolved, closed, rejected

            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Targeted indexes for high-frequency queries
            $table->index(['branch_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['priority', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

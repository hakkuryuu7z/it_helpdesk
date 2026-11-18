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
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->unique();
            $table->text('business_requirements');
            $table->text('problem_description');
            $table->text('expected_outcome');
            $table->text('technical_notes')->nullable();
            $table->string('dev_priority', 20)->nullable();
            $table->string('estimated_effort', 50)->nullable();
            $table->string('stage', 30)->default('submitted');
            $table->text('testing_notes')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('stage');
            $table->index('dev_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};

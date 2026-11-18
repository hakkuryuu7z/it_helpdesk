<?php

namespace App\Services;

use App\Models\TicketNumberSequence;
use Illuminate\Support\Facades\DB;

class TicketNumberService
{
    /**
     * Generate a concurrency-safe, gap-free, unique ticket number.
     * Format: HD-YYYY-000001 (e.g. HD-2026-000001)
     *
     * Uses pessimistic locking (SELECT ... FOR UPDATE) inside a database transaction.
     */
    public function generateNextTicketNumber(?int $year = null): string
    {
        $year = $year ?? (int) now()->format('Y');

        return DB::transaction(function () use ($year) {
            // Lock the counter row for the year
            $sequence = TicketNumberSequence::where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                // If sequence doesn't exist for this year, create it safely
                $sequence = TicketNumberSequence::create([
                    'year' => $year,
                    'current_value' => 0,
                    'updated_at' => now(),
                ]);

                // Re-lock
                $sequence = TicketNumberSequence::where('year', $year)
                    ->lockForUpdate()
                    ->first();
            }

            $sequence->current_value += 1;
            $sequence->updated_at = now();
            $sequence->save();

            $paddedCounter = str_pad((string) $sequence->current_value, 6, '0', STR_PAD_LEFT);

            return sprintf('HD-%d-%s', $year, $paddedCounter);
        });
    }
}

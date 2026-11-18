<?php

namespace App\Models;

use App\Enums\FeatureRequestStage;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'business_requirements',
        'problem_description',
        'expected_outcome',
        'technical_notes',
        'dev_priority',
        'estimated_effort',
        'stage',
        'testing_notes',
        'deployment_notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => FeatureRequestStage::class,
            'completed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(FeatureRequestActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope feature requests based on parent ticket's visibility to the user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return $query;
        }

        return $query->whereHas('ticket', function (Builder $ticketQuery) use ($user) {
            $ticketQuery->forUser($user);
        });
    }

    /**
     * Query filter scope for stage, dev priority, branch, search.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (!empty($filters['dev_priority'])) {
            $query->where('dev_priority', $filters['dev_priority']);
        }

        if (!empty($filters['branch_id'])) {
            $query->whereHas('ticket', function ($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_requirements', 'ilike', "%{$search}%")
                  ->orWhere('problem_description', 'ilike', "%{$search}%")
                  ->orWhere('expected_outcome', 'ilike', "%{$search}%")
                  ->orWhereHas('ticket', function ($tq) use ($search) {
                      $tq->where('ticket_number', 'ilike', "%{$search}%")
                         ->orWhere('subject', 'ilike', "%{$search}%");
                  });
            });
        }

        return $query;
    }
}

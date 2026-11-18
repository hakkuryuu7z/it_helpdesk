<?php

namespace App\Models;

use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'branch_id',
        'created_by',
        'category_id',
        'assigned_to',
        'type',
        'subject',
        'description',
        'priority',
        'impact',
        'urgency',
        'status',
        'resolution_notes',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'impact' => TicketImpact::class,
            'urgency' => TicketUrgency::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignedEdp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class)->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('created_at', 'desc');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->orderBy('created_at', 'desc');
    }

    public function featureRequest(): HasOne
    {
        return $this->hasOne(FeatureRequest::class);
    }

    /**
     * Primary Scope: Enforce backend authorization boundaries based on User Role and Branch Assignment.
     * Mandatory Rules:
     * 1. Regular user can only view tickets they created.
     * 2. Branch EDP can only access tickets from assigned branches.
     * 3. Central EDP can access tickets across branches.
     * 4. Super Admin has system-wide access.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return $query;
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            $assignedBranchIds = $user->assignedBranches()->pluck('branches.id')->toArray();
            return $query->whereIn('branch_id', $assignedBranchIds);
        }

        if ($user->hasRole(UserRole::BranchAdmin->value)) {
            return $query->where('branch_id', $user->branch_id);
        }

        // Regular User: Strictly own tickets
        return $query->where('created_by', $user->id);
    }

    /**
     * Query filter scope for list views and reports.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                  ->orWhere('subject', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }
}

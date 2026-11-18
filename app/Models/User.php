<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'department_id',
        'name',
        'email',
        'username',
        'phone',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Format phone number for WhatsApp notifications.
     */
    public function routeNotificationForWhatsapp(): ?string
    {
        if (empty($this->phone)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_edp_assignments', 'user_id', 'branch_id')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Verify whether this user is authorized to manage or service a specific branch.
     */
    public function canServiceBranch(int $branchId): bool
    {
        if ($this->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])) {
            return true;
        }

        if ($this->hasRole(UserRole::BranchEdp->value)) {
            return $this->assignedBranches()->where('branches.id', $branchId)->exists();
        }

        return (int) $this->branch_id === $branchId;
    }
}

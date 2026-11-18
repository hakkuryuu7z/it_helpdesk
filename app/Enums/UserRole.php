<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CentralEdp = 'central_edp';
    case BranchEdp = 'branch_edp';
    case BranchAdmin = 'branch_admin';
    case RegularUser = 'regular_user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::CentralEdp => 'Central EDP',
            self::BranchEdp => 'Branch EDP',
            self::BranchAdmin => 'Branch Admin',
            self::RegularUser => 'Regular User',
        };
    }
}

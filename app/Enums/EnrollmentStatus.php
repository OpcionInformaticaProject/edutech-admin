<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa', self::Inactive => 'Inactiva', self::Withdrawn => 'Retirada',
            self::Completed => 'Completada', self::Suspended => 'Suspendida', self::Pending => 'Pendiente',
        };
    }
}

<?php

namespace App\Enums;

use App\Support\Status;

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
        return Status::label($this);
    }
}

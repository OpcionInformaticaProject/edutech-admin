<?php

namespace App\Support;

use BackedEnum;

class Status
{
    public static function label(BackedEnum|string|null $status): string
    {
        $value = $status instanceof BackedEnum ? $status->value : $status;

        return match ($value) {
            'active' => 'Activo', 'inactive' => 'Inactivo', 'withdrawn' => 'Retirado',
            'completed' => 'Completado', 'pending' => 'Pendiente', 'suspended' => 'Suspendido',
            'needs_review' => 'Por revisar', 'complete' => 'Revisado', 'paid' => 'Pagado',
            'pending_payment' => 'Pendiente de pago', 'reversed' => 'Reversado', 'valid' => 'Válido',
            default => $value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estado',
        };
    }

    public static function badge(BackedEnum|string|null $status): string
    {
        $value = $status instanceof BackedEnum ? $status->value : $status;

        return match ($value) {
            'active', 'complete', 'paid', 'valid' => 'status-active',
            'pending', 'pending_payment', 'needs_review' => 'status-pending',
            'inactive', 'withdrawn', 'suspended', 'reversed' => 'status-withdrawn',
            'completed' => 'status-completed', default => 'muted',
        };
    }

    public static function paymentMethod(?string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo', 'nequi' => 'Nequi', 'transfer' => 'Transferencia',
            'card' => 'Tarjeta', 'historical' => 'Pago histórico', 'other' => 'Otro',
            default => $method ? ucfirst(str_replace('_', ' ', $method)) : 'No informado',
        };
    }
}

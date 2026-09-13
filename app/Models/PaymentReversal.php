<?php

namespace App\Models;

use Database\Factories\PaymentReversalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReversal extends Model
{
    /** @use HasFactory<PaymentReversalFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reversed_at' => 'datetime'];
    }
}

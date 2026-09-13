<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2', 'historical' => 'boolean'];
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    public function reversal()
    {
        return $this->hasOne(PaymentReversal::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'previous_balance' => 'decimal:2', 'payment_amount' => 'decimal:2', 'new_balance' => 'decimal:2'];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

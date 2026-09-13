<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['enrollment_date' => 'date', 'billing_start_date' => 'date', 'agreed_amount' => 'decimal:2', 'historical' => 'boolean', 'status' => EnrollmentStatus::class];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function validPayments()
    {
        return $this->payments()->where('status', 'valid');
    }

    public function importRows()
    {
        return $this->hasMany(ImportRow::class);
    }

    public function reviews()
    {
        return $this->hasMany(EnrollmentReview::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) ($this->valid_payments_sum_amount ?? $this->validPayments()->sum('amount'));
    }

    public function getBalanceAttribute(): ?float
    {
        return $this->agreed_amount === null ? null : (float) $this->agreed_amount - $this->total_paid;
    }
}

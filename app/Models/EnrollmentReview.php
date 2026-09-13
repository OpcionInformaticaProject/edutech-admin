<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EnrollmentReview extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function importRow()
    {
        return $this->belongsTo(ImportRow::class);
    }
}

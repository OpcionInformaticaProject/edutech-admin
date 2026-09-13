<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'authorized_pickup' => 'boolean'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

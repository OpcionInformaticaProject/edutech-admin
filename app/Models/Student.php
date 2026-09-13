<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'active' => 'boolean'];
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function getFullNameAttribute(): string
    {
        return "$this->first_name $this->last_name";
    }
}

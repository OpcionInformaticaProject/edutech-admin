<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}

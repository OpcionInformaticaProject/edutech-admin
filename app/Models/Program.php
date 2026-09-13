<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}

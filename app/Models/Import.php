<?php

namespace App\Models;

use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['summary' => 'array', 'mapping' => 'array', 'confirmed_at' => 'datetime'];
    }

    public function rows()
    {
        return $this->hasMany(ImportRow::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

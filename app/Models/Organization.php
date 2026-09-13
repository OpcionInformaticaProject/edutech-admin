<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'branding' => 'array'];
    }

    public function campuses()
    {
        return $this->hasMany(Campus::class);
    }

    public function brandingPath(string $variant = 'logo'): ?string
    {
        $branding = $this->branding ?? [];

        return $branding[$variant] ?? ($variant === 'login_logo' ? ($branding['logo'] ?? null) : null);
    }

    public function brandingUrl(string $variant = 'logo'): ?string
    {
        $path = $this->brandingPath($variant);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function brandingDataUri(string $variant = 'logo'): ?string
    {
        $path = $this->brandingPath($variant);
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'address',
        'contact_number',
        'email',
    ];

    public static function getSingleton(): static
    {
        return static::firstOrCreate([]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    protected function casts(): array
    {
        return [];
    }
}

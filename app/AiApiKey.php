<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiApiKey extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'model',
        'api_key',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Preferred key for AI calls (lowest sort_order, then id).
     */
    public static function firstActive(): ?self
    {
        return static::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * Masked value for display (never expose full key in HTML).
     */
    public function getApiKeyMaskedAttribute(): string
    {
        $plain = $this->api_key;
        if ($plain === null || $plain === '') {
            return '';
        }
        $len = strlen($plain);
        if ($len <= 8) {
            return str_repeat('•', min(12, $len));
        }

        return substr($plain, 0, 4).'…'.substr($plain, -4);
    }
}

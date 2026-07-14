<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    public static function putValue(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $public = false): self
    {
        return static::query()->updateOrCreate(['key' => $key], [
            'group' => $group,
            'value' => $type === 'json' ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value,
            'type' => $type,
            'is_public' => $public,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const BOOLEAN_KEYS = [
        'tax_enabled',
        'service_enabled',
        'allow_negative_stock',
        'method_cash',
        'method_qris',
        'method_card',
    ];

    private const INTEGER_KEYS = [
        'tax_rate_bp',
        'service_rate_bp',
        'rounding_unit',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->all()[$key] ?? null;

        if ($raw === null) {
            return $default;
        }

        return $this->cast($key, $raw);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $this->toStorage($key, $value)]);
        Cache::forget('settings.all');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public function forget(): void
    {
        Cache::forget('settings.all');
    }

    private function cast(string $key, string $raw): mixed
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $raw === '1' || $raw === 'true';
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (int) $raw;
        }

        return $raw;
    }

    private function toStorage(string $key, mixed $value): string
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}

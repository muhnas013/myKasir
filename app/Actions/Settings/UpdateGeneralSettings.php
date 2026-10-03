<?php

namespace App\Actions\Settings;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;

class UpdateGeneralSettings
{
    private const ALLOWED_KEYS = [
        'outlet_name',
        'outlet_address',
        'outlet_phone',
        'logo_path',
        'qris_image_path',
        'tax_enabled',
        'tax_rate_bp',
        'service_enabled',
        'service_rate_bp',
        'rounding_unit',
        'receipt_footer',
        'allow_negative_stock',
        'method_cash',
        'method_qris',
        'method_card',
    ];

    public function __construct(private SettingService $settings) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function handle(array $values, User $actor): void
    {
        $values = array_intersect_key($values, array_flip(self::ALLOWED_KEYS));

        DB::transaction(function () use ($values, $actor) {
            $old = [];
            $new = [];

            foreach ($values as $key => $value) {
                $previous = $this->settings->get($key);

                if ($previous === $value) {
                    continue;
                }

                $old[$key] = $previous;
                $new[$key] = $value;
                $this->settings->set($key, $value);
            }

            if ($old === []) {
                return;
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'setting.updated',
                'subject_type' => 'settings',
                'old_values' => $old,
                'new_values' => $new,
                'ip' => request()->ip(),
            ]);
        });
    }
}

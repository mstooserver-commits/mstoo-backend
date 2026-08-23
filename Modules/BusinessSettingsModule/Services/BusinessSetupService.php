<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\BusinessSettings;

class BusinessSetupService
{
    public function ensureDefaults(): void
    {
        foreach ($this->defaults() as $item) {
            $exists = BusinessSettings::query()
                ->where('key_name', $item['key_name'])
                ->where('settings_type', $item['settings_type'])
                ->exists();
            if ($exists) {
                continue;
            }

            BusinessSettings::query()->create([
                'key_name' => $item['key_name'],
                'live_values' => $item['live_values'],
                'test_values' => $item['live_values'],
                'settings_type' => $item['settings_type'],
                'mode' => 'live',
                'is_active' => 1,
            ]);
        }
    }

    public function save(string $key, $value, string $type): BusinessSettings
    {
        return BusinessSettings::query()->updateOrCreate(
            ['key_name' => $key, 'settings_type' => $type],
            [
                'key_name' => $key,
                'live_values' => $value,
                'test_values' => $value,
                'settings_type' => $type,
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function maskPayment(array $values): array
    {
        foreach (payment_secret_keys() as $key) {
            if (isset($values[$key]) && $values[$key] !== '') {
                $values[$key] = mask_secret($values[$key]);
            }
        }
        return $values;
    }

    public function mergePaymentSecrets(array $submitted, array $existing): array
    {
        foreach (payment_secret_keys() as $key) {
            if (!array_key_exists($key, $submitted)) {
                continue;
            }
            if (is_masked_secret($submitted[$key] ?? '')) {
                $submitted[$key] = $existing[$key] ?? '';
            }
        }
        return $submitted;
    }

    private function defaults(): array
    {
        return [
            [
                'key_name' => 'maintenance_mode',
                'settings_type' => 'system_maintenance',
                'live_values' => [
                    'status' => 0,
                    'message' => 'MSTOO is temporarily unavailable. Please try again later.',
                    'start_at' => null,
                    'end_at' => null,
                ],
            ],
            ['key_name' => 'customer_self_registration', 'settings_type' => 'customer_config', 'live_values' => 1],
            ['key_name' => 'customer_can_cancel_booking', 'settings_type' => 'service_setup', 'live_values' => 1],
            ['key_name' => 'business_latitude', 'settings_type' => 'business_information', 'live_values' => ''],
            ['key_name' => 'business_longitude', 'settings_type' => 'business_information', 'live_values' => ''],
        ];
    }
}

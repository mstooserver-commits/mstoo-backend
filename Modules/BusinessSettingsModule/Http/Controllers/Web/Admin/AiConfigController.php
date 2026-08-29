<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

class AiConfigController extends Controller
{
    public function index(): View
    {
        $row = BusinessSettings::query()
            ->where('key_name', 'ai_configuration')
            ->where('settings_type', 'third_party')
            ->first();
        $values = is_array($row?->live_values) ? $row->live_values : [];
        $values['api_key_masked'] = $this->mask($values['api_key'] ?? '');

        return view('businesssettingsmodule::admin.ai-config', compact('values'));
    }

    public function save(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => 'nullable|in:0,1',
            'provider' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:80',
            'base_url' => 'nullable|url',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:8000',
            'system_prompt' => 'nullable|string|max:4000',
            'api_key' => 'nullable|string|max:255',
        ]);

        $existing = BusinessSettings::query()
            ->where('key_name', 'ai_configuration')
            ->where('settings_type', 'third_party')
            ->first();
        $current = is_array($existing?->live_values) ? $existing->live_values : [];
        $apiKey = (string) $request->input('api_key');
        if ($apiKey === '' || str_contains($apiKey, '****')) {
            $apiKey = $current['api_key'] ?? '';
        }

        $values = [
            'enabled' => $request->boolean('enabled') ? 1 : 0,
            'provider' => $request->input('provider', 'openai'),
            'model' => $request->input('model', 'gpt-4o-mini'),
            'base_url' => $request->input('base_url'),
            'temperature' => $request->input('temperature', 0.7),
            'max_tokens' => $request->input('max_tokens', 512),
            'system_prompt' => $request->input('system_prompt'),
            'api_key' => $apiKey,
        ];

        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'ai_configuration', 'settings_type' => 'third_party'],
            [
                'live_values' => $values,
                'test_values' => $values,
                'mode' => 'live',
                'is_active' => $values['enabled'],
            ]
        );

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    private function mask(string $secret): string
    {
        if ($secret === '') {
            return '';
        }
        $tail = substr($secret, -4);

        return 'sk-**************' . $tail;
    }
}

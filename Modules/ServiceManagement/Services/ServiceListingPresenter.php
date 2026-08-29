<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;

class ServiceListingPresenter
{
    public function decorate($services, bool $compact = true)
    {
        $items = $this->items($services);
        $ids = $items->pluck('id')->filter()->unique()->values();
        $grouped = $ids->isEmpty()
            ? collect()
            : Variation::withoutGlobalScopes()
                ->whereIn('service_id', $ids)
                ->orderBy('price')
                ->get()
                ->groupBy('service_id');

        $items->each(function ($service) use ($grouped, $compact) {
            $this->decorateOne($service, $grouped->get($service->id, collect()), $compact);
        });

        return $services;
    }

    public function decorateOne($service, $variations = null, bool $compact = true)
    {
        if ($variations === null) {
            $variations = Variation::withoutGlobalScopes()
                ->where('service_id', $service->id)
                ->orderBy('price')
                ->get();
        }

        $variations = collect($variations)->values();
        $service->setRelation('variations', $variations);

        $zoneId = Config::get('zone_id');
        $filtered = $zoneId ? $variations->where('zone_id', $zoneId)->values() : $variations;
        if ($filtered->isEmpty()) {
            $filtered = $variations;
        }

        $minPrice = (float) ($filtered->min('price') ?? 0);
        $maxPrice = (float) ($filtered->max('price') ?? 0);
        $first = $filtered->first();
        $unit = $this->priceUnit($first ? ($first->variant ?: $first->variant_key) : 'day');
        $symbol = function_exists('currency_symbol') ? (currency_symbol() ?: '₹') : '₹';
        $defaultPrice = $first ? (float) $first->price : $minPrice;
        $displayPrice = $defaultPrice > 0 ? $this->formatDisplayPrice($defaultPrice, $symbol, $unit) : '';
        $attrs = $service->getAttributes();
        $coverFile = $this->imageFileName($attrs['cover_image'] ?? null);
        $thumbFile = $this->imageFileName($attrs['thumbnail'] ?? null) ?: $coverFile;
        $coverUrl = $this->imageUrl($coverFile);
        $thumbUrl = $this->imageUrl($thumbFile);

        $service->setAttribute('variations_app_format', [
            'zone_id' => $zoneId,
            'default_price' => $defaultPrice,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'currency_symbol' => $symbol,
            'price_unit' => $unit,
            'display_price' => $displayPrice,
            'zone_wise_variations' => $filtered->map(function ($data) use ($symbol, $unit) {
                $rowUnit = $this->priceUnit($data['variant'] ?? $data['variant_key'] ?? $unit);
                $price = (float) $data['price'];

                return [
                    'variant_key' => $data['variant_key'],
                    'variant_name' => $data['variant'],
                    'price' => $price,
                    'display_price' => $price > 0 ? $this->formatDisplayPrice($price, $symbol, $rowUnit) : '',
                ];
            })->values()->all(),
        ]);

        $service->setAttribute('name', trim(preg_replace('/\s+/', ' ', (string) $service->name)));
        $service->setAttribute('price', $defaultPrice);
        $service->setAttribute('min_price', $minPrice);
        $service->setAttribute('max_price', $maxPrice);
        $service->setAttribute('min_max_price', ['min' => $minPrice, 'max' => $maxPrice]);
        $service->setAttribute('display_price', $displayPrice);
        $service->setAttribute('currency_symbol', $symbol);
        $service->setAttribute('price_unit', $unit);
        $service->setAttribute('cover_image', $coverFile);
        $service->setAttribute('thumbnail', $thumbFile);
        $service->setAttribute('cover_image_full_url', $coverUrl);
        $service->setAttribute('thumbnail_full_url', $thumbUrl);

        $short = trim(preg_replace('/\s+/', ' ', strip_tags((string) $service->short_description)));
        $service->setAttribute('short_description', $short !== '' ? $short : null);

        if ($compact) {
            $service->setAttribute('description', null);
            $service->makeHidden([
                'variations',
                'description',
                'deleted_at',
                'faqs',
                'campaign_discount',
                'service_discount',
            ]);
        }

        return $service;
    }

    public function formatDisplayPrice(float $amount, ?string $symbol = null, string $unit = 'day'): string
    {
        $symbol = $symbol ?: (function_exists('currency_symbol') ? (currency_symbol() ?: '₹') : '₹');
        $formatted = abs($amount - round($amount)) < 0.001
            ? number_format($amount, 0, '.', ',')
            : number_format($amount, 2, '.', ',');

        return $symbol . $formatted . '/' . $unit;
    }

    public function priceUnit($raw): string
    {
        $value = strtolower(trim((string) $raw));
        $value = preg_replace('/^(per|\/)\s*/', '', $value) ?: '';

        if ($value === '' || str_contains($value, 'day') || in_array($value, ['d', 'daily'], true)) {
            return 'day';
        }
        if (str_contains($value, 'hour') || in_array($value, ['hr', 'hrs', 'hourly'], true)) {
            return 'hour';
        }
        if (str_contains($value, 'week') || $value === 'weekly') {
            return 'week';
        }
        if (str_contains($value, 'month') || $value === 'monthly') {
            return 'month';
        }

        return $value;
    }

    public function imageFileName(?string $file): ?string
    {
        if (!$file || in_array($file, ['def.png', 'default.png', 'placeholder.png'], true)) {
            return null;
        }
        if (preg_match('#^https?://#i', $file)) {
            $path = parse_url($file, PHP_URL_PATH);
            $name = $path ? basename($path) : null;

            return $name ?: $file;
        }

        return ltrim($file, '/');
    }

    public function imageUrl(?string $file): ?string
    {
        if (!$file || in_array($file, ['def.png', 'default.png', 'placeholder.png'], true)) {
            return null;
        }
        if (preg_match('#^https?://#i', $file)) {
            return $file;
        }

        $file = $this->imageFileName($file);
        if (!$file) {
            return null;
        }
        if (str_starts_with($file, 'storage/')) {
            return url($file);
        }

        return url('storage/app/public/service/' . $file);
    }

    private function items($services): Collection
    {
        if ($services instanceof AbstractPaginator) {
            return collect($services->items());
        }
        if ($services instanceof Collection) {
            return $services;
        }
        if ($services instanceof Service) {
            return collect([$services]);
        }

        return collect($services);
    }
}

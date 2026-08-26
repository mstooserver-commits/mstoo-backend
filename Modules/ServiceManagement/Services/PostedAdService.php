<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;

class PostedAdService
{
    public const MAX_ADS = 50;

    private const OPTIONAL_FIELDS = [
        'short_description', 'availability', 'latitude', 'longitude', 'location',
        'contact_info', 'deposits', 'doc_required', 'additional_info', 'delivery_pickup',
        't_and_c', 'safety', 'utilities', 'pets',
        'vehicle_type', 'vehicle_brand', 'model_year', 'mileage', 'fuel_type', 'transmission', 'condition',
        'service_type', 'equipment_type', 'equipment_brand', 'power_source', 'weight', 'dimensions',
        'property_type', 'bedrooms', 'bathrooms', 'square_footage', 'furnished',
        'furniture_type', 'furniture_brand',
        'electronic_type', 'electronic_brand', 'operating_system', 'screen_size', 'storage_capacity',
        'camera_resolution', 'connectivity',
        'cloth_type', 'cloth_brand', 'cloth_size',
        'availability_date',
    ];

    public function createMany(array $ads, ?string $defaultUserId = null, bool $allowUserOverride = true): array
    {
        $created = [];
        $failed = [];

        foreach (array_values($ads) as $index => $row) {
            if (!is_array($row)) {
                $failed[] = ['row' => $index + 1, 'message' => 'Invalid ad payload'];
                continue;
            }
            if ($this->isEmptyRow($row)) {
                continue;
            }
            try {
                $userId = $defaultUserId;
                if ($allowUserOverride) {
                    $user = $this->resolveUser(
                        $this->value($row, 'user_id', 'customer_id', 'added_by'),
                        $this->value($row, 'user_email', 'email'),
                        $this->value($row, 'user_phone', 'phone'),
                        $defaultUserId
                    );
                    $userId = $user->id;
                } elseif (!$userId) {
                    throw new \InvalidArgumentException('A user is required to post ads');
                }
                $created[] = $this->create($row, $userId);
            } catch (\Throwable $exception) {
                $failed[] = [
                    'row' => $index + 1,
                    'name' => $this->value($row, 'name'),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'created_count' => count($created),
            'failed_count' => count($failed),
            'created' => $created,
            'failed' => $failed,
        ];
    }

    public function create(array $payload, string $userId): Service
    {
        $name = $this->value($payload, 'name');
        $price = $this->value($payload, 'price');
        $description = $this->value($payload, 'description');

        if (!$name) {
            throw new \InvalidArgumentException('Ad name is required');
        }
        if ($price === null || $price === '') {
            throw new \InvalidArgumentException('Price is required');
        }
        if (!is_numeric($price) || (float) $price < 0) {
            throw new \InvalidArgumentException('Price must be a number');
        }
        if (!$description) {
            throw new \InvalidArgumentException('Description is required');
        }

        $subCategory = $this->resolveSubCategory($payload);
        $zone = DB::table('zones')->first();
        if (!$zone) {
            throw new \RuntimeException('No zone is configured');
        }

        $cover = $this->storeImage($this->value($payload, 'cover_image', 'cover_image_url', 'thumbnail'));
        $gallery = $this->storeGallery($payload, $cover);

        return DB::transaction(function () use ($payload, $userId, $name, $price, $description, $subCategory, $zone, $cover, $gallery) {
            $service = new Service();
            $service->name = $name;
            $service->category_id = $subCategory->parent_id;
            $service->sub_category_id = $subCategory->id;
            $service->description = $description;
            $service->short_description = $this->value($payload, 'short_description') ?: Str::limit(strip_tags((string) $description), 180);
            $service->cover_image = $cover;
            $service->thumbnail = $cover;
            $service->thumbnails = json_encode($gallery);
            $service->added_by = $userId;
            $service->is_active = 1;
            $service->availability = $this->value($payload, 'availability');
            $service->latitude = $this->value($payload, 'latitude');
            $service->longitude = $this->value($payload, 'longitude');

            $catName = strtolower((string) ($this->value($payload, 'cat_name', 'category') ?: ''));
            $this->applyCategoryFields($service, $payload, $catName);

            $columns = Schema::getColumnListing('services');
            foreach (self::OPTIONAL_FIELDS as $field) {
                $value = $this->value($payload, $field);
                if ($value !== null && $value !== '' && in_array($field, $columns, true) && $service->{$field} === null) {
                    $service->{$field} = $value;
                }
            }

            if ($this->value($payload, 'is_featured') === 'yes') {
                $service->is_featured = 'yes';
                $service->order_id = $this->value($payload, 'order_id');
                $service->payment_id = $this->value($payload, 'payment_id');
                $service->signature = $this->value($payload, 'signature');
            }

            $service->save();

            $duration = $this->value($payload, 'rent_duration', 'variant') ?: 'per day';
            Variation::withoutGlobalScopes()->create([
                'variant' => $duration,
                'variant_key' => $duration,
                'zone_id' => $zone->id,
                'price' => round((float) $price, 2),
                'service_id' => $service->id,
            ]);

            return $service->fresh();
        });
    }

    public function resolveUser($id, $email, $phone, ?string $fallbackId = null): User
    {
        $query = User::query()->whereNotIn('user_type', ADMIN_USER_TYPES);
        $user = null;

        if ($id) {
            $user = (clone $query)->where('id', $id)->first();
        }
        if (!$user && $email) {
            $user = (clone $query)->where('email', $email)->first();
        }
        if (!$user && $phone) {
            $user = (clone $query)->where('phone', $phone)->first();
        }
        if (!$user && $fallbackId) {
            $user = (clone $query)->where('id', $fallbackId)->first();
        }
        if (!$user) {
            throw new \InvalidArgumentException('Customer not found. Use user_id, user_email or user_phone.');
        }

        return $user;
    }

    public function templateRows(): array
    {
        return [[
            'user_email' => 'customer@example.com',
            'user_phone' => '',
            'name' => 'Honda City for rent',
            'sub_category' => 'Cars',
            'sub_category_id' => '',
            'cat_name' => 'vehicle',
            'price' => 1500,
            'rent_duration' => 'per day',
            'description' => 'Well maintained Honda City available for daily rent.',
            'short_description' => 'Honda City on rent',
            'location' => 'Pune',
            'latitude' => '18.5204',
            'longitude' => '73.8567',
            'availability' => 'available',
            'contact_info' => '9876543210',
            'cover_image_url' => '',
            'vehicle_type' => 'car',
            'vehicle_brand' => 'Honda',
            'condition' => 'good',
        ]];
    }

    private function resolveSubCategory(array $payload)
    {
        $id = $this->value($payload, 'sub_category_id');
        $name = $this->value($payload, 'sub_category', 'sub_category_name');

        $query = DB::table('categories')->where('position', 2)->where('is_active', 1);
        $category = null;
        if ($id) {
            $category = (clone $query)->where('id', $id)->first();
        }
        if (!$category && $name) {
            $category = (clone $query)->where('name', $name)->first()
                ?: (clone $query)->where('name', 'like', '%' . $name . '%')->first();
        }
        if (!$category) {
            throw new \InvalidArgumentException('Sub category is required. Use sub_category_id or sub_category name.');
        }

        return $category;
    }

    private function applyCategoryFields(Service $service, array $payload, string $catName): void
    {
        if ($catName === 'vehicle') {
            $service->vehicle_type = $this->value($payload, 'vehicle_type');
            $service->vehicle_brand = $this->value($payload, 'vehicle_brand');
            $service->model_year = $this->value($payload, 'model_year');
            $service->mileage = $this->value($payload, 'mileage');
            $service->fuel_type = $this->value($payload, 'fuel_type');
            $service->transmission = $this->value($payload, 'transmission');
            $service->condition = $this->value($payload, 'condition');
            $service->location = $this->value($payload, 'location');
            $service->availability_date = $this->value($payload, 'availability_date');
            $service->contact_info = $this->value($payload, 'contact_info');
            $service->deposits = $this->value($payload, 'deposits');
            $service->doc_required = $this->value($payload, 'doc_required');
            $service->additional_info = $this->value($payload, 'additional_info');
            $service->delivery_pickup = $this->value($payload, 'delivery_pickup');
            $service->safety = $this->value($payload, 'safety_guidelines', 'safety');
            $service->t_and_c = $this->value($payload, 't_and_c');
        } elseif ($catName === 'electronic') {
            $service->electronic_type = $this->value($payload, 'electronic_type', 'equipment_type');
            $service->electronic_brand = $this->value($payload, 'electronic_brand', 'equipment_brand');
            $service->model_year = $this->value($payload, 'model_year');
            $service->condition = $this->value($payload, 'condition');
            $service->operating_system = $this->value($payload, 'operating_system');
            $service->screen_size = $this->value($payload, 'screen_size');
            $service->storage_capacity = $this->value($payload, 'storage_capacity');
            $service->camera_resolution = $this->value($payload, 'camera_resolution');
            $service->connectivity = $this->value($payload, 'connectivity');
        } elseif ($catName === 'cloth') {
            $service->cloth_type = $this->value($payload, 'cloth_type');
            $service->cloth_brand = $this->value($payload, 'cloth_brand');
            $service->cloth_size = $this->value($payload, 'cloth_size');
            $service->condition = $this->value($payload, 'condition');
        }
    }

    private function storeGallery(array $payload, string $cover): array
    {
        $images = $this->value($payload, 'images') ?: [];
        if (is_string($images)) {
            $images = preg_split('/[|,]/', $images) ?: [];
        }
        if (!is_array($images)) {
            $images = [$images];
        }

        $stored = [];
        foreach (array_slice($images, 0, 8) as $image) {
            if ($image === null || $image === '') {
                continue;
            }
            $stored[] = $this->storeImage($image);
        }
        if (!$stored) {
            $stored[] = $cover;
        }

        return $stored;
    }

    private function storeImage($image): string
    {
        $directory = storage_path('app/public/service/');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if ($image instanceof UploadedFile && $image->isValid()) {
            $name = Str::random(12) . '.' . ($image->getClientOriginalExtension() ?: 'jpg');
            $image->move($directory, $name);
            return $name;
        }

        if (!is_string($image) || trim($image) === '') {
            return 'def.png';
        }

        $image = trim($image);
        if (str_starts_with($image, 'data:image')) {
            $image = substr($image, strpos($image, ',') + 1);
        }

        if (preg_match('#^https?://#i', $image)) {
            try {
                $contents = @file_get_contents($image);
                if ($contents) {
                    $name = Str::random(12) . '.jpg';
                    file_put_contents($directory . $name, $contents);
                    return $name;
                }
            } catch (\Throwable $exception) {
                return 'def.png';
            }
            return 'def.png';
        }

        if (strlen($image) > 80 && !str_contains($image, '.')) {
            $decoded = base64_decode($image, true);
            if ($decoded) {
                $name = Str::random(12) . '.jpg';
                file_put_contents($directory . $name, $decoded);
                return $name;
            }
        }

        if (is_file($directory . $image)) {
            return $image;
        }

        return 'def.png';
    }

    private function value(array $row, string ...$keys)
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = $value;
        }
        foreach ($keys as $key) {
            $lookup = $this->normalizeKey($key);
            if (array_key_exists($lookup, $normalized) && $normalized[$lookup] !== null && $normalized[$lookup] !== '') {
                return is_string($normalized[$lookup]) ? trim($normalized[$lookup]) : $normalized[$lookup];
            }
        }
        return null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(trim(preg_replace('/[\s\-]+/', '_', $key)));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value instanceof UploadedFile) {
                return false;
            }
            if (is_string($value) && trim($value) !== '') {
                return false;
            }
            if (is_numeric($value)) {
                return false;
            }
        }
        return true;
    }
}

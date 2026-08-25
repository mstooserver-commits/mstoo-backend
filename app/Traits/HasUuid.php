<?php
namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait HasUuid
{
    public function initializeHasUuid()
    {
        $this->setKeyType('string');
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            $current = $model->{$key} ?? null;
            if (empty($current)) {
                $model->{$key} = Uuid::uuid4()->toString();
            } else {
                $model->{$key} = (string) $current;
            }
            $model->incrementing = false;
        }, 0);
    }
}

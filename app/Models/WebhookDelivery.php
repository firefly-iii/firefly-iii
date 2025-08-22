<?php

declare(strict_types=1);

namespace FireflyIII\Models;

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use ReturnsIntegerIdTrait;

    /**
     * Get the ID
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    protected function key(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
}

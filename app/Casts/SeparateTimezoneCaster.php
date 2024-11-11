<?php

declare(strict_types=1);

namespace FireflyIII\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SeparateTimezoneCaster
 *
 * Checks if the object has a separate _tz value. If it does, it will use that timezone to parse the date.
 * If it is NULL, it will use the system's timezone.
 *
 * At some point a user's database consists entirely of UTC dates, and we won't need this anymore. However,
 * the completeness of this migration is not yet guaranteed.
 */
class SeparateTimezoneCaster implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ('' === $value || null === $value) {
            return null;
        }
        $timeZone = $attributes[sprintf('%s_tz', $key)] ?? config('app.timezone');

        return Carbon::parse($value, $timeZone)->setTimezone(config('app.timezone'));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}

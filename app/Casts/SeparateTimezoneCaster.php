<?php


/*
 * SeparateTimezoneCaster.php
 * Copyright (c) 2025 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

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

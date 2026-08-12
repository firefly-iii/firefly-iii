<?php


/*
 * PeriodStatistic.php
 * Copyright (c) 2026 james@firefly-iii.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\Casts\SeparateTimezoneCaster;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Carbon $start
 * @property Carbon $end
 * @property string $amount
 */
class PeriodStatistic extends Model
{
    use ReturnsIntegerUserIdTrait;

    public function primaryStatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function secondaryStatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tertiaryStatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'start'      => SeparateTimezoneCaster::class,
            'end'        => SeparateTimezoneCaster::class,
        ];
    }

    protected function count(): Attribute
    {
        return Attribute::make(get: static fn ($value): int => (int) $value);
    }
}

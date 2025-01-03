<?php

/**
 * RecurrenceRepetition.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 */
class RecurrenceRepetition extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    #[\Deprecated] /** @deprecated  */
    public const int WEEKEND_DO_NOTHING    = 1;

    #[\Deprecated] /** @deprecated  */
    public const int WEEKEND_SKIP_CREATION = 2;

    #[\Deprecated] /** @deprecated  */
    public const int WEEKEND_TO_FRIDAY     = 3;

    #[\Deprecated] /** @deprecated  */
    public const int WEEKEND_TO_MONDAY     = 4;

    protected $casts
                                           = [
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'repetition_type'   => 'string',
            'repetition_moment' => 'string',
            'repetition_skip'   => 'int',
            'weekend'           => 'int',
        ];

    protected $fillable                    = ['recurrence_id', 'weekend', 'repetition_type', 'repetition_moment', 'repetition_skip'];

    /** @var string The table to store the data in */
    protected $table                       = 'recurrences_repetitions';

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    protected function casts(): array
    {
        return [
            // 'weekend' => RecurrenceRepetitionWeekend::class,
        ];
    }

    protected function recurrenceId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    protected function repetitionSkip(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    protected function weekend(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
}

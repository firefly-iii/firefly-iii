<?php

/**
 * PiggyBankRepetition.php
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

use Carbon\Carbon;
use FireflyIII\Casts\SeparateTimezoneCaster;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPiggyBankRepetition
 */
class PiggyBankRepetition extends Model
{
    use ReturnsIntegerIdTrait;

    protected $casts
                        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'startdate'       => SeparateTimezoneCaster::class,
            'targetdate'      => SeparateTimezoneCaster::class,
            'virtual_balance' => 'string',
        ];

    protected $fillable = ['piggy_bank_id', 'startdate', 'startdate_tz', 'targetdate', 'targetdate_tz', 'currentamount'];

    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $target): EloquentBuilder
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('targetdate', $target->format('Y-m-d'));
    }

    /**
     * @return EloquentBuilder
     */
    public function scopeRelevantOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where(
            static function (EloquentBuilder $q) use ($date): void {
                $q->where('startdate', '<=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('startdate');
            }
        )
            ->where(
                static function (EloquentBuilder $q) use ($date): void {
                    $q->where('targetdate', '>=', $date->format('Y-m-d 00:00:00'));
                    $q->orWhereNull('targetdate');
                }
            )
        ;
    }

    /**
     * @param mixed $value
     */
    public function setCurrentamountAttribute($value): void
    {
        $this->attributes['currentamount'] = (string)$value;
    }

    /**
     * Get the amount
     */
    protected function currentamount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function piggyBankId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

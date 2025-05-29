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

use Illuminate\Database\Eloquent\Attributes\Scope;
use Carbon\Carbon;
use FireflyIII\Casts\SeparateTimezoneCaster;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiggyBankRepetition extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = ['piggy_bank_id', 'start_date', 'start_date_tz', 'target_date', 'target_date_tz', 'current_amount'];

    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    #[Scope]
    protected function onDates(EloquentBuilder $query, Carbon $start, Carbon $target): EloquentBuilder
    {
        return $query->where('start_date', $start->format('Y-m-d'))->where('target_date', $target->format('Y-m-d'));
    }

    /**
     * @return EloquentBuilder
     */
    #[Scope]
    protected function relevantOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where(
            static function (EloquentBuilder $q) use ($date): void {
                $q->where('start_date', '<=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('start_date');
            }
        )
            ->where(
                static function (EloquentBuilder $q) use ($date): void {
                    $q->where('target_date', '>=', $date->format('Y-m-d 00:00:00'));
                    $q->orWhereNull('target_date');
                }
            )
        ;
    }

    /**
     * @param mixed $value
     */
    public function setCurrentAmountAttribute($value): void
    {
        $this->attributes['current_amount'] = (string) $value;
    }

    /**
     * Get the amount
     */
    protected function currentAmount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string) $value,
        );
    }

    protected function piggyBankId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
    protected function casts(): array
    {
        return [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'start_date'      => SeparateTimezoneCaster::class,
            'target_date'     => SeparateTimezoneCaster::class,
            'virtual_balance' => 'string',
        ];
    }
}

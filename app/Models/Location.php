<?php

/**
 * Location.php
 * Copyright (c) 2020 james@firefly-iii.org
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 */
class Location extends Model
{
    use ReturnsIntegerIdTrait;

    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'zoomLevel'  => 'int',
            'latitude'   => 'float',
            'longitude'  => 'float',
        ];

    protected $fillable = ['locatable_id', 'locatable_type', 'latitude', 'longitude', 'zoom_level'];

    /**
     * Add rules for locations.
     */
    public static function requestRules(array $rules): array
    {
        $rules['latitude']   = 'numeric|min:-90|max:90|nullable|required_with:longitude';
        $rules['longitude']  = 'numeric|min:-180|max:180|nullable|required_with:latitude';
        $rules['zoom_level'] = 'numeric|min:0|max:80|nullable|required_with:latitude';

        return $rules;
    }

    public function accounts(): MorphMany
    {
        return $this->morphMany(Account::class, 'locatable');
    }

    /**
     * Get all the owning attachable models.
     */
    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactionJournals(): MorphMany
    {
        return $this->morphMany(TransactionJournal::class, 'locatable');
    }

    protected function locatableId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
}

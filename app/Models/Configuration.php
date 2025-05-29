<?php

/**
 * Configuration.php
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

use Illuminate\Database\Eloquent\Casts\Attribute;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function Safe\json_decode;
use function Safe\json_encode;

class Configuration extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $table = 'configuration';

    /**
     * TODO can be replaced with native laravel code.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function data(): Attribute
    {
        return Attribute::make(get: fn($value) => json_decode((string) $value), set: fn($value) => ['data' => json_encode($value)]);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}

<?php

/**
 * AccountMeta.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMeta extends Model
{
    use ReturnsIntegerIdTrait;

    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    protected $fillable = ['account_id', 'name', 'data'];
    protected $table    = 'account_meta';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getDataAttribute(mixed $value): string
    {
        return (string) \Safe\json_decode($value, true);
    }

    public function setDataAttribute(mixed $value): void
    {
        $this->attributes['data'] = \Safe\json_encode($value);
    }
}

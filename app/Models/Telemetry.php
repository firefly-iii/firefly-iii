<?php

/**
 * Telemetry.php
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
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * FireflyIII\Models\Telemetry
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $submitted
 * @property int|null $user_id
 * @property string $installation_id
 * @property string $type
 * @property string $key
 * @property array $value
 * @method static Builder|Telemetry newModelQuery()
 * @method static Builder|Telemetry newQuery()
 * @method static Builder|Telemetry query()
 * @method static Builder|Telemetry whereCreatedAt($value)
 * @method static Builder|Telemetry whereId($value)
 * @method static Builder|Telemetry whereInstallationId($value)
 * @method static Builder|Telemetry whereKey($value)
 * @method static Builder|Telemetry whereSubmitted($value)
 * @method static Builder|Telemetry whereType($value)
 * @method static Builder|Telemetry whereUpdatedAt($value)
 * @method static Builder|Telemetry whereUserId($value)
 * @method static Builder|Telemetry whereValue($value)
 * @mixin Eloquent
 */
class Telemetry extends Model
{
    /** @var string */
    protected $table = 'telemetry';

    /** @var array */
    protected $fillable = ['installation_id', 'submitted', 'user_id', 'key', 'type', 'value'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'submitted' => 'datetime',
            'value'     => 'array',
        ];

}

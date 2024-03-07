<?php

/*
 * AuditLogEntry.php
 * Copyright (c) 2022 james@firefly-iii.org
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
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AuditLogEntry
 *
 * @property \Eloquent|Model $auditable
 * @property \Eloquent|Model $changer
 *
 * @method static Builder|AuditLogEntry                            newModelQuery()
 * @method static Builder|AuditLogEntry                            newQuery()
 * @method static \Illuminate\Database\Query\Builder|AuditLogEntry onlyTrashed()
 * @method static Builder|AuditLogEntry                            query()
 * @method static \Illuminate\Database\Query\Builder|AuditLogEntry withTrashed()
 * @method static \Illuminate\Database\Query\Builder|AuditLogEntry withoutTrashed()
 *
 * @property int         $id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property null|Carbon $deleted_at
 * @property int         $auditable_id
 * @property string      $auditable_type
 * @property int         $changer_id
 * @property string      $changer_type
 * @property string      $action
 * @property null|array  $before
 * @property null|array  $after
 *
 * @method static Builder|AuditLogEntry whereAction($value)
 * @method static Builder|AuditLogEntry whereAfter($value)
 * @method static Builder|AuditLogEntry whereAuditableId($value)
 * @method static Builder|AuditLogEntry whereAuditableType($value)
 * @method static Builder|AuditLogEntry whereBefore($value)
 * @method static Builder|AuditLogEntry whereChangerId($value)
 * @method static Builder|AuditLogEntry whereChangerType($value)
 * @method static Builder|AuditLogEntry whereCreatedAt($value)
 * @method static Builder|AuditLogEntry whereDeletedAt($value)
 * @method static Builder|AuditLogEntry whereId($value)
 * @method static Builder|AuditLogEntry whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class AuditLogEntry extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'before'     => 'array',
            'after'      => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function changer(): MorphTo
    {
        return $this->morphTo();
    }

    protected function auditableId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function changerId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

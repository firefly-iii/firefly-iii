<?php
/**
 * RecurrenceMeta.php
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
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * FireflyIII\Models\RecurrenceMeta
 *
 * @property int         $id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property null|Carbon $deleted_at
 * @property int         $recurrence_id
 * @property string      $name
 * @property mixed       $value
 * @property Recurrence  $recurrence
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta newQuery()
 * @method static Builder|RecurrenceMeta                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereValue($value)
 * @method static Builder|RecurrenceMeta                               withTrashed()
 * @method static Builder|RecurrenceMeta                               withoutTrashed()
 *
 * @mixin Eloquent
 */
class RecurrenceMeta extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'name'       => 'string',
            'value'      => 'string',
        ];

    protected $fillable = ['recurrence_id', 'name', 'value'];

    /** @var string The table to store the data in */
    protected $table = 'recurrences_meta';

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    protected function recurrenceId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

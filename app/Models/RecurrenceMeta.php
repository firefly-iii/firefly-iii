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


use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Class RecurrenceMeta
 *
 * @property string                          $name
 * @property string                          $value
 * @property int                             $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int                             $recurrence_id
 * @property-read Recurrence                 $recurrence
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta newQuery()
 * @method static Builder|RecurrenceMeta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceMeta whereValue($value)
 * @method static Builder|RecurrenceMeta withTrashed()
 * @method static Builder|RecurrenceMeta withoutTrashed()
 * @mixin Eloquent
 */
class RecurrenceMeta extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'name'       => 'string',
            'value'      => 'string',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['recurrence_id', 'name', 'value'];
    /** @var string The table to store the data in */
    protected $table = 'recurrences_meta';

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

}

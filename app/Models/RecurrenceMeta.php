<?php
/**
 * RecurrenceMeta.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class RecurrenceMeta
 *
 * @property string $name
 * @property string $value
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $recurrence_id
 * @property-read \FireflyIII\Models\Recurrence $recurrence
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceMeta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceMeta whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceMeta withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceMeta withoutTrashed()
 * @mixin \Eloquent
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

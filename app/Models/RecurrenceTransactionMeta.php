<?php
/**
 * RecurrenceTransactionMeta.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class RecurrenceTransactionMeta
 *
 * @property string $name
 * @property string $value
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $rt_id
 * @property-read \FireflyIII\Models\RecurrenceTransaction $recurrenceTransaction
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceTransactionMeta onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereRtId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceTransactionMeta whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceTransactionMeta withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceTransactionMeta withoutTrashed()
 * @mixin \Eloquent
 */
class RecurrenceTransactionMeta extends Model
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
    protected $fillable = ['rt_id', 'name', 'value'];
    /** @var string The table to store the data in */
    protected $table = 'rt_meta';

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function recurrenceTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurrenceTransaction::class, 'rt_id');
    }

}

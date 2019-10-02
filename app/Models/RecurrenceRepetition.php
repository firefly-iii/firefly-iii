<?php
/**
 * RecurrenceRepetition.php
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
 * Class RecurrenceRepetition
 *
 * @property string         $repetition_type
 * @property string         $repetition_moment
 * @property int            $repetition_skip
 * @property int            $weekend
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $updated_at
 * @property int            $id
 * @property int $recurrence_id
 * @property-read \FireflyIII\Models\Recurrence $recurrence
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceRepetition onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereRepetitionMoment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereRepetitionSkip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereRepetitionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\RecurrenceRepetition whereWeekend($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceRepetition withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\RecurrenceRepetition withoutTrashed()
 * @mixin \Eloquent
 */
class RecurrenceRepetition extends Model
{
    /** @var int */
    public const WEEKEND_DO_NOTHING = 1;
    /** @var int */
    public const WEEKEND_SKIP_CREATION = 2;
    /** @var int */
    public const WEEKEND_TO_FRIDAY = 3;
    /** @var int */
    public const WEEKEND_TO_MONDAY = 4;
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'repetition_type'   => 'string',
            'repetition_moment' => 'string',
            'repetition_skip'   => 'int',
            'weekend'           => 'int',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['recurrence_id', 'weekend', 'repetition_type', 'repetition_moment', 'repetition_skip'];
    /** @var string The table to store the data in */
    protected $table = 'recurrences_repetitions';

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }
}

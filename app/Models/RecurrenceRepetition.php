<?php
/**
 * RecurrenceRepetition.php
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
 * FireflyIII\Models\RecurrenceRepetition
 *
 * @property int         $id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property null|Carbon $deleted_at
 * @property int         $recurrence_id
 * @property string      $repetition_type
 * @property string      $repetition_moment
 * @property int         $repetition_skip
 * @property int         $weekend
 * @property Recurrence  $recurrence
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition newQuery()
 * @method static Builder|RecurrenceRepetition                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereRepetitionMoment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereRepetitionSkip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereRepetitionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceRepetition whereWeekend($value)
 * @method static Builder|RecurrenceRepetition                               withTrashed()
 * @method static Builder|RecurrenceRepetition                               withoutTrashed()
 *
 * @mixin Eloquent
 */
class RecurrenceRepetition extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    public const int WEEKEND_DO_NOTHING    = 1;
    public const int WEEKEND_SKIP_CREATION = 2;
    public const int WEEKEND_TO_FRIDAY     = 3;
    public const int WEEKEND_TO_MONDAY     = 4;

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

    protected $fillable = ['recurrence_id', 'weekend', 'repetition_type', 'repetition_moment', 'repetition_skip'];

    /** @var string The table to store the data in */
    protected $table = 'recurrences_repetitions';

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

    protected function repetitionSkip(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function weekend(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

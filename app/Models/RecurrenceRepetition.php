<?php
/**
 * RecurrenceRepetition.php
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

/**
 * Class RecurrenceRepetition
 *
 * @property string         $repetition_type
 * @property string         $repetition_moment
 * @property int            $repetition_skip
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $updated_at
 * @property int            $id
 */
class RecurrenceRepetition extends Model
{
    /** @var array */
    protected $casts
        = [
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'repetition_type'   => 'string',
            'repetition_moment' => 'string',
            'repetition_skip'   => 'int',
        ];
    protected $fillable = ['recurrence_id', 'repetition_type', 'repetition_moment', 'repetition_skip'];
    /** @var string */
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
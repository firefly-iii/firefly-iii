<?php
/**
 * PiggyBankRepetition.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PiggyBankRepetition.
 */
class PiggyBankRepetition extends Model
{
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
            'startdate'  => 'date',
            'targetdate' => 'date',
        ];
    /** @var array */
    protected $dates = ['startdate', 'targetdate'];
    /** @var array */
    protected $fillable = ['piggy_bank_id', 'startdate', 'targetdate', 'currentamount'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $start
     * @param Carbon          $target
     *
     * @return EloquentBuilder
     */
    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $target)
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('targetdate', $target->format('Y-m-d'));
    }

    /**
     * @param EloquentBuilder $query
     * @param Carbon          $date
     *
     * @return mixed
     */
    public function scopeRelevantOnDate(EloquentBuilder $query, Carbon $date)
    {
        return $query->where(
            function (EloquentBuilder $q) use ($date) {
                $q->where('startdate', '<=', $date->format('Y-m-d 00:00:00'));
                $q->orWhereNull('startdate');
            }
        )
                     ->where(
                         function (EloquentBuilder $q) use ($date) {
                             $q->where('targetdate', '>=', $date->format('Y-m-d 00:00:00'));
                             $q->orWhereNull('targetdate');
                         }
                     );
    }

    /**
     * @param $value
     */
    public function setCurrentamountAttribute($value)
    {
        $this->attributes['currentamount'] = strval(round($value, 12));
    }
}

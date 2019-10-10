<?php
/**
 * PiggyBankRepetition.php
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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PiggyBankRepetition.
 *
 * @property string $currentamount
 * @property Carbon $startdate
 * @property Carbon $targetdate
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $piggy_bank_id
 * @property-read \FireflyIII\Models\PiggyBank $piggyBank
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition onDates(\Carbon\Carbon $start, \Carbon\Carbon $target)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition relevantOnDate(\Carbon\Carbon $date)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereCurrentamount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition wherePiggyBankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereStartdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereTargetdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\PiggyBankRepetition whereUpdatedAt($value)
 * @mixin \Eloquent
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
            'startdate'  => 'date',
            'targetdate' => 'date',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['piggy_bank_id', 'startdate', 'targetdate', 'currentamount'];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param Carbon          $start
     * @param Carbon          $target
     *
     * @return EloquentBuilder
     */
    public function scopeOnDates(EloquentBuilder $query, Carbon $start, Carbon $target): EloquentBuilder
    {
        return $query->where('startdate', $start->format('Y-m-d'))->where('targetdate', $target->format('Y-m-d'));
    }

    /**
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setCurrentamountAttribute($value): void
    {
        $this->attributes['currentamount'] = (string)$value;
    }
}

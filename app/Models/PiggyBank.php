<?php
/**
 * PiggyBank.php
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
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Steam;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PiggyBank
 *
 * @package FireflyIII\Models
 */
class PiggyBank extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
            'startdate'  => 'date',
            'targetdate' => 'date',
            'order'      => 'int',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
    protected $fillable = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate'];
    protected $hidden   = ['targetamount_encrypted', 'encrypted'];

    /**
     * @param PiggyBank $value
     *
     * @return PiggyBank
     */
    public static function routeBinder(PiggyBank $value)
    {
        if (auth()->check()) {
            if (intval($value->account->user_id) === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns PiggyBankRepetition
     */
    public function currentRelevantRep(): PiggyBankRepetition
    {
        if (!is_null($this->currentRep)) {
            return $this->currentRep;
        }
        // repeating piggy banks are no longer supported.
        /** @var PiggyBankRepetition $rep */
        $rep = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
        if (is_null($rep)) {
            return new PiggyBankRepetition();
        }
        $this->currentRep = $rep;

        return $rep;
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getSuggestedMonthlyAmount(): string
    {
        $savePerMonth = '0';
        if ($this->targetdate && $this->currentRelevantRep()->currentamount < $this->targetamount) {
            $now             = Carbon::now();
            $diffInMonths    = $now->diffInMonths($this->targetdate, false);
            $remainingAmount = bcsub($this->targetamount, $this->currentRelevantRep()->currentamount);

            // more than 1 month to go and still need money to save:
            if ($diffInMonths > 0 && bccomp($remainingAmount, '0') === 1) {
                $savePerMonth = bcdiv($remainingAmount, strval($diffInMonths));
            }

            // less than 1 month to go but still need money to save:
            if ($diffInMonths === 0 && bccomp($remainingAmount, '0') === 1) {
                $savePerMonth = $remainingAmount;
            }
        }

        return $savePerMonth;
    }

    /**
     *
     * @param Carbon $date
     *
     * @return string
     */
    public function leftOnAccount(Carbon $date): string
    {

        $balance = Steam::balanceIgnoreVirtual($this->account, $date);
        /** @var PiggyBank $p */
        foreach ($this->account->piggyBanks as $piggyBank) {
            $currentAmount = $piggyBank->currentRelevantRep()->currentamount ?? '0';

            $balance = bcsub($balance, $currentAmount);
        }

        return $balance;

    }

    /**
     * Get all of the piggy bank's notes.
     */
    public function notes()
    {
        return $this->morphMany('FireflyIII\Models\Note', 'noteable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    /**
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @param $value
     */
    public function setTargetamountAttribute($value)
    {
        $this->attributes['targetamount'] = strval(round($value, 12));
    }
}

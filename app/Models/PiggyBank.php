<?php
/**
 * PiggyBank.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Steam;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PiggyBank extends Model
{
    use SoftDeletes;

    protected $dates  = ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];
    protected $fillable
                      = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate'];
    protected $hidden = ['targetamount_encrypted', 'encrypted'];

    /**
     * @param PiggyBank $value
     *
     * @return PiggyBank
     */
    public static function routeBinder(PiggyBank $value)
    {
        if (auth()->check()) {
            if ($value->account->user_id == auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
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

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
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
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @param $value
     */
    public function setTargetamountAttribute($value)
    {
        $this->attributes['targetamount'] = strval(round($value, 2));
    }
}

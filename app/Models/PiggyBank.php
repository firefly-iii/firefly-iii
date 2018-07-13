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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PiggyBank.
 *
 * @property Carbon  $targetdate
 * @property Carbon  $startdate
 * @property string  $targetamount
 * @property int     $id
 * @property string  $name
 * @property Account $account
 * @property Carbon  $updated_at
 * @property Carbon  $created_at
 * @property int     $order
 * @property bool    $active
 * @property int     $account_id
 *
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'startdate'  => 'date',
            'targetdate' => 'date',
            'order'      => 'int',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array */
    protected $dates = ['startdate', 'targetdate'];
    /** @var array */
    protected $fillable = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate', 'active'];
    /** @var array */
    protected $hidden = ['targetamount_encrypted', 'encrypted'];

    /**
     * @param string $value
     *
     * @return PiggyBank
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value): PiggyBank
    {
        if (auth()->check()) {
            $piggyBankId = (int)$value;
            $piggyBank   = self::where('piggy_banks.id', $piggyBankId)
                               ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                               ->where('accounts.user_id', auth()->user()->id)->first(['piggy_banks.*']);
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active.
     *
     * @deprecated
     * @returns PiggyBankRepetition
     */
    public function currentRelevantRep(): PiggyBankRepetition
    {
        if (null !== $this->currentRep) {
            return $this->currentRep;
        }
        // repeating piggy banks are no longer supported.
        /** @var PiggyBankRepetition $rep */
        $rep = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
        if (null === $rep) {
            return new PiggyBankRepetition();
        }
        $this->currentRep = $rep;

        return $rep;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getNameAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param Carbon $date
     *
     * @deprecated
     * @return string
     */
    public function leftOnAccount(Carbon $date): string
    {
        $balance = app('steam')->balanceIgnoreVirtual($this->account, $date);
        /** @var PiggyBank $piggyBank */
        foreach ($this->account->piggyBanks as $piggyBank) {
            $currentAmount = $piggyBank->currentRelevantRep()->currentamount ?? '0';

            $balance = bcsub($balance, $currentAmount);
        }

        return $balance;
    }

    /**
     * @codeCoverageIgnore
     * Get all of the piggy bank's notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany(PiggyBankEvent::class);
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany(PiggyBankRepetition::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setNameAttribute($value)
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setTargetamountAttribute($value)
    {
        $this->attributes['targetamount'] = (string)$value;
    }
}

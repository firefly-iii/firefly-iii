<?php
/**
 * Bill.php
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

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Bill.
 */
class Bill extends Model
{
    use SoftDeletes, ValidatingTrait;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'date'            => 'date',
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
        ];
    /**
     * @var array
     */
    protected $fillable
        = ['name', 'match', 'amount_min', 'match_encrypted', 'name_encrypted', 'user_id', 'amount_max', 'date', 'repeat_freq', 'skip',
           'automatch', 'active',];
    /**
     * @var array
     */
    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];
    /**
     * @var array
     */
    protected $rules = ['name' => 'required|between:1,200'];

    /**
     * @param string $value
     *
     * @return Bill
     */
    public static function routeBinder(string $value): Bill
    {
        if (auth()->check()) {
            $billId = intval($value);
            $bill   = auth()->user()->bills()->find($billId);
            if (!is_null($bill)) {
                return $bill;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachments()
    {
        return $this->morphMany('FireflyIII\Models\Attachment', 'attachable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getMatchAttribute($value)
    {
        if (1 === intval($this->match_encrypted)) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        if (1 === intval($this->name_encrypted)) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountMaxAttribute($value)
    {
        $this->attributes['amount_max'] = strval($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setAmountMinAttribute($value)
    {
        $this->attributes['amount_min'] = strval($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setMatchAttribute($value)
    {
        $encrypt                             = config('firefly.encryption');
        $this->attributes['match']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['match_encrypted'] = $encrypt;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setNameAttribute($value)
    {
        $encrypt                            = config('firefly.encryption');
        $this->attributes['name']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['name_encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}

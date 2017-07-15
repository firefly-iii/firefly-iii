<?php
/**
 * Bill.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Bill
 *
 * @package FireflyIII\Models
 */
class Bill extends Model
{

    use ValidatingTrait;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'date',
            'updated_at'      => 'date',
            'deleted_at'      => 'date',
            'date'            => 'date',
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
        ];
    /** @var array */
    protected $dates  = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable
                      = ['name', 'match', 'amount_min', 'match_encrypted', 'name_encrypted', 'user_id', 'amount_max', 'date', 'repeat_freq', 'skip',
                         'automatch', 'active',];
    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];
    protected $rules  = ['name' => 'required|between:1,200',];

    /**
     * @param Bill $value
     *
     * @return Bill
     */
    public static function routeBinder(Bill $value)
    {
        if (auth()->check()) {
            if ($value->user_id === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getMatchAttribute($value)
    {

        if (intval($this->match_encrypted) === 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->name_encrypted) === 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param $value
     */
    public function setAmountMaxAttribute($value)
    {
        $this->attributes['amount_max'] = strval(round($value, 12));
    }

    /**
     * @param $value
     */
    public function setAmountMinAttribute($value)
    {
        $this->attributes['amount_min'] = strval(round($value, 12));
    }

    /**
     * @param $value
     */
    public function setMatchAttribute($value)
    {
        $encrypt                             = config('firefly.encryption');
        $this->attributes['match']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['match_encrypted'] = $encrypt;
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $encrypt                            = config('firefly.encryption');
        $this->attributes['name']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['name_encrypted'] = $encrypt;
    }

    /**
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }


}

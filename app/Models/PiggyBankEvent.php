<?php
/**
 * PiggyBankEvent.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PiggyBankEvent
 *
 * @package FireflyIII\Models
 */
class PiggyBankEvent extends Model
{

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'date'       => 'date',
        ];
    protected $dates    = ['created_at', 'updated_at', 'date'];
    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'amount'];
    protected $hidden   = ['amount_encrypted'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggyBank()
    {
        return $this->belongsTo('FireflyIII\Models\PiggyBank');
    }

    /**
     * @param $value
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = strval(round($value, 2));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionJournal');
    }

}

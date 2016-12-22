<?php
/**
 * AvailableBudget.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AvailableBudget
 *
 * @package FireflyIII\Models
 */
class AvailableBudget extends Model
{
    use SoftDeletes;
    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'start_date', 'end_date'];
    /** @var array */
    protected $fillable = ['user_id', 'transaction_currency_id', 'amount', 'start_date', 'end_date'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionCurrency()
    {
        return $this->belongsTo('FireflyIII\Models\TransactionCurrency');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
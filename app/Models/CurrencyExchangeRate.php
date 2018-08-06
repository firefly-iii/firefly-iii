<?php
/**
 * CurrencyExchangeRate.php
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CurrencyExchange.
 *
 * @property int                 $id
 * @property Carbon              $created_at
 * @property Carbon              $updated_at
 * @property TransactionCurrency $fromCurrency
 * @property TransactionCurrency $toCurrency
 * @property float               $rate
 * @property Carbon              $date
 * @property int                 $from_currency_id
 * @property int                 $to_currency_id
 *
 */
class CurrencyExchangeRate extends Model
{
    /** @var array Convert these fields to other data types */
    protected $casts
        = [
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
            'user_id'          => 'int',
            'from_currency_id' => 'int',
            'to_currency_id'   => 'int',
            'date'             => 'datetime',
        ];

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class, 'from_currency_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class, 'to_currency_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

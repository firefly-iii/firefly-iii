<?php
/**
 * CurrencyExchangeRate.php
 * Copyright (c) 2019 james@firefly-iii.org
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
 * @property string|null $deleted_at
 * @property int $user_id
 * @property float|null $user_rate
 * @property-read \FireflyIII\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereFromCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereToCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\CurrencyExchangeRate whereUserRate($value)
 * @mixin \Eloquent
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

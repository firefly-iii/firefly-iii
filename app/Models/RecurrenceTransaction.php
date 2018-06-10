<?php
/**
 * RecurrenceTransaction.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 * Class RecurrenceTransaction
 *
 * @property int                                    $transaction_currency_id,
 * @property int                                    $foreign_currency_id
 * @property int                                    $source_account_id
 * @property int                                    $destination_account_id
 * @property string                                 $amount
 * @property string                                 $foreign_amount
 * @property string                                 $description
 * @property \FireflyIII\Models\TransactionCurrency $transactionCurrency
 * @property \FireflyIII\Models\TransactionCurrency $foreignCurrency
 * @property \FireflyIII\Models\Account             $sourceAccount
 * @property \FireflyIII\Models\Account             $destinationAccount
 * @property \Illuminate\Support\Collection         $recurrenceTransactionMeta
 */
class RecurrenceTransaction extends Model
{
    protected $table = 'recurrences_transactions';

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function foreignCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function recurrenceTransactionMeta(): HasMany
    {
        return $this->hasMany(recurrenceTransactionMeta::class,'rt_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }
}
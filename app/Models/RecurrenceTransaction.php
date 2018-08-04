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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *
 * Class RecurrenceTransaction
 *
 * @property int                                    $transaction_currency_id,
 * @property int                                    $foreign_currency_id
 * @property int                                    $source_id
 * @property int                                    $destination_id
 * @property string                                 $amount
 * @property string                                 $foreign_amount
 * @property string                                 $description
 * @property \FireflyIII\Models\TransactionCurrency $transactionCurrency
 * @property \FireflyIII\Models\TransactionCurrency $foreignCurrency
 * @property \FireflyIII\Models\Account             $sourceAccount
 * @property \FireflyIII\Models\Account             $destinationAccount
 * @property \Illuminate\Support\Collection         $recurrenceTransactionMeta
 * @property int                                    $id
 * @property Recurrence                             $recurrence
 */
class RecurrenceTransaction extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
            'amount'         => 'string',
            'foreign_amount' => 'string',
            'description'    => 'string',
        ];
    /** @var array Fields that can be filled */
    protected $fillable
        = ['recurrence_id', 'transaction_currency_id', 'foreign_currency_id', 'source_id', 'destination_id', 'amount', 'foreign_amount',
           'description'];
    /** @var string The table to store the data in */
    protected $table = 'recurrences_transactions';

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_id');
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
        return $this->hasMany(RecurrenceTransactionMeta::class, 'rt_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_id');
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

<?php
/**
 * RecurrenceTransaction.php
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
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * FireflyIII\Models\RecurrenceTransaction
 *
 * @property int                                    $id
 * @property null|Carbon                            $created_at
 * @property null|Carbon                            $updated_at
 * @property null|Carbon                            $deleted_at
 * @property int                                    $recurrence_id
 * @property int                                    $transaction_currency_id
 * @property null|int|string                        $foreign_currency_id
 * @property int                                    $source_id
 * @property int                                    $destination_id
 * @property string                                 $amount
 * @property string                                 $foreign_amount
 * @property string                                 $description
 * @property Account                                $destinationAccount
 * @property null|TransactionCurrency               $foreignCurrency
 * @property Recurrence                             $recurrence
 * @property Collection|RecurrenceTransactionMeta[] $recurrenceTransactionMeta
 * @property null|int                               $recurrence_transaction_meta_count
 * @property Account                                $sourceAccount
 * @property TransactionCurrency                    $transactionCurrency
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction newQuery()
 * @method static Builder|RecurrenceTransaction                               onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereDestinationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereForeignAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereForeignCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereRecurrenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereUpdatedAt($value)
 * @method static Builder|RecurrenceTransaction                               withTrashed()
 * @method static Builder|RecurrenceTransaction                               withoutTrashed()
 *
 * @property null|int $transaction_type_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RecurrenceTransaction whereTransactionTypeId($value)
 *
 * @property null|TransactionType $transactionType
 *
 * @mixin Eloquent
 */
class RecurrenceTransaction extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
        = [
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
            'amount'         => 'string',
            'foreign_amount' => 'string',
            'description'    => 'string',
        ];

    protected $fillable
        = [
            'recurrence_id',
            'transaction_currency_id',
            'foreign_currency_id',
            'source_id',
            'destination_id',
            'amount',
            'foreign_amount',
            'description',
        ];

    /** @var string The table to store the data in */
    protected $table = 'recurrences_transactions';

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_id');
    }

    public function foreignCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    public function recurrenceTransactionMeta(): HasMany
    {
        return $this->hasMany(RecurrenceTransactionMeta::class, 'rt_id');
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_id');
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function destinationId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function foreignAmount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function recurrenceId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function sourceId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function transactionCurrencyId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function userId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

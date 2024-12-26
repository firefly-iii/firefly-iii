<?php

/**
 * Bill.php
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

use FireflyIII\Casts\SeparateTimezoneCaster;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\Support\Models\ReturnsIntegerUserIdTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperBill
 */
class Bill extends Model
{
    use ReturnsIntegerIdTrait;
    use ReturnsIntegerUserIdTrait;
    use SoftDeletes;

    protected $casts
                      = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'date'            => SeparateTimezoneCaster::class,
            'end_date'        => SeparateTimezoneCaster::class,
            'extension_date'  => SeparateTimezoneCaster::class,
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
            'amount_min'      => 'string',
            'amount_max'      => 'string',
            'native_amount_min'      => 'string',
            'native_amount_max'      => 'string',
        ];

    protected $fillable
                      = [
            'name',
            'match',
            'amount_min',
            'user_id',
            'user_group_id',
            'amount_max',
            'date',
            'date_tz',
            'repeat_freq',
            'skip',
            'automatch',
            'active',
            'transaction_currency_id',
            'end_date',
            'extension_date',
            'end_date_tz',
            'extension_date_tz',
            'native_amount_min',
            'native_amount_max',
        ];

    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $billId = (int) $value;

            /** @var User $user */
            $user   = auth()->user();

            /** @var null|Bill $bill */
            $bill   = $user->bills()->find($billId);
            if (null !== $bill) {
                return $bill;
            }
        }

        throw new NotFoundHttpException();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get all the tags for the post.
     */
    public function objectGroups(): MorphToMany
    {
        return $this->morphToMany(ObjectGroup::class, 'object_groupable');
    }

    /**
     * @param mixed $value
     */
    public function setAmountMaxAttribute($value): void
    {
        $this->attributes['amount_max'] = (string) $value;
    }

    /**
     * @param mixed $value
     */
    public function setAmountMinAttribute($value): void
    {
        $this->attributes['amount_min'] = (string) $value;
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * Get the max amount
     */
    protected function amountMax(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string) $value,
        );
    }

    /**
     * Get the min amount
     */
    protected function amountMin(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string) $value,
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    /**
     * Get the skip
     */
    protected function skip(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }

    protected function transactionCurrencyId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
}

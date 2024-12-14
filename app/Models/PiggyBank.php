<?php

/**
 * PiggyBank.php
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

use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @mixin IdeHelperPiggyBank
 */
class PiggyBank extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    protected $casts
                        = [
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'deleted_at'    => 'datetime',
            'start_date'    => 'date',
            'target_date'   => 'date',
            'order'         => 'int',
            'active'        => 'boolean',
            'encrypted'     => 'boolean',
            'target_amount' => 'string',
        ];

    protected $fillable = ['name', 'order', 'target_amount', 'start_date', 'start_date_tz', 'target_date', 'target_date_tz', 'active', 'transaction_currency_id'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $piggyBankId = (int)$value;
            $piggyBank   = self::where('piggy_banks.id', $piggyBankId)
                ->leftJoin('account_piggy_bank', 'account_piggy_bank.piggy_bank_id', '=', 'piggy_banks.id')
                ->leftJoin('accounts', 'accounts.id', '=', 'account_piggy_bank.account_id')
                ->where('accounts.user_id', auth()->user()->id)->first(['piggy_banks.*'])
            ;
            if (null !== $piggyBank) {
                return $piggyBank;
            }
        }

        throw new NotFoundHttpException();
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all the piggy bank's notes.
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

    public function piggyBankEvents(): HasMany
    {
        return $this->hasMany(PiggyBankEvent::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class)->withPivot('current_amount');
    }

    public function piggyBankRepetitions(): HasMany
    {
        return $this->hasMany(PiggyBankRepetition::class);
    }

    /**
     * @param mixed $value
     */
    public function setTargetAmountAttribute($value): void
    {
        $this->attributes['target_amount'] = (string)$value;
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    /**
     * Get the max amount
     */
    protected function targetAmount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }
}

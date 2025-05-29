<?php

/**
 * PiggyBankEvent.php
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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiggyBankEvent extends Model
{
    use ReturnsIntegerIdTrait;

    protected $fillable = ['piggy_bank_id', 'transaction_journal_id', 'date', 'date_tz', 'amount', 'native_amount'];

    protected $hidden   = ['amount_encrypted'];

    public function piggyBank(): BelongsTo
    {
        return $this->belongsTo(PiggyBank::class);
    }

    /**
     * @param mixed $value
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = (string) $value;
    }

    public function transactionJournal(): BelongsTo
    {
        return $this->belongsTo(TransactionJournal::class);
    }

    /**
     * Get the amount
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string) $value,
        );
    }

    protected function piggyBankId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int) $value,
        );
    }
    protected function casts(): array
    {
        return [
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
            'date'          => SeparateTimezoneCaster::class,
            'amount'        => 'string',
            'native_amount' => 'string',
        ];
    }
}

<?php

/**
 * AutoBudget.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use Deprecated;
use FireflyIII\Handlers\Observer\AutoBudgetObserver;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AutoBudgetObserver::class])]
class AutoBudget extends Model
{
    use ReturnsIntegerIdTrait;
    use SoftDeletes;

    #[Deprecated]
    /** @deprecated */
    public const int AUTO_BUDGET_ADJUSTED = 3;

    #[Deprecated]
    /** @deprecated */
    public const int AUTO_BUDGET_RESET    = 1;

    #[Deprecated]
    /** @deprecated */
    public const int AUTO_BUDGET_ROLLOVER = 2;
    protected $casts
                                          = [
            'amount'        => 'string',
            'native_amount' => 'string',
        ];
    protected $fillable                   = ['budget_id', 'amount', 'period', 'native_amount'];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (string)$value,
        );
    }

    protected function budgetId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function casts(): array
    {
        return [
            // 'auto_budget_type' => AutoBudgetType::class,
        ];
    }

    protected function transactionCurrencyId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

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

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * FireflyIII\Models\AutoBudget
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $budget_id
 * @property int $transaction_currency_id
 * @property int $auto_budget_type
 * @property string $amount
 * @property string $period
 * @property-read \FireflyIII\Models\Budget $budget
 * @property-read \FireflyIII\Models\TransactionCurrency $transactionCurrency
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget newQuery()
 * @method static Builder|AutoBudget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereAutoBudgetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereTransactionCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoBudget whereUpdatedAt($value)
 * @method static Builder|AutoBudget withTrashed()
 * @method static Builder|AutoBudget withoutTrashed()
 * @mixin Eloquent
 */
class AutoBudget extends Model
{
    /** @var int When the auto-budget resets every period automatically. */
    public const AUTO_BUDGET_RESET = 1;
    /** @var int When the auto-budget adds an amount every period automatically */
    public const AUTO_BUDGET_ROLLOVER = 2;

    use SoftDeletes;

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
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

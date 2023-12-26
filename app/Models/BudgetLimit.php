<?php

/**
 * BudgetLimit.php
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
use FireflyIII\Events\Model\BudgetLimit\Created;
use FireflyIII\Events\Model\BudgetLimit\Deleted;
use FireflyIII\Events\Model\BudgetLimit\Updated;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\BudgetLimit
 *
 * @property int                      $id
 * @property null|Carbon              $created_at
 * @property null|Carbon              $updated_at
 * @property int                      $budget_id
 * @property int                      $transaction_currency_id
 * @property Carbon                   $start_date
 * @property null|Carbon              $end_date
 * @property string                   $amount
 * @property string                   $spent
 * @property null|string              $period
 * @property int|string               $generated
 * @property Budget                   $budget
 * @property null|TransactionCurrency $transactionCurrency
 *
 * @method static Builder|BudgetLimit newModelQuery()
 * @method static Builder|BudgetLimit newQuery()
 * @method static Builder|BudgetLimit query()
 * @method static Builder|BudgetLimit whereAmount($value)
 * @method static Builder|BudgetLimit whereBudgetId($value)
 * @method static Builder|BudgetLimit whereCreatedAt($value)
 * @method static Builder|BudgetLimit whereEndDate($value)
 * @method static Builder|BudgetLimit whereGenerated($value)
 * @method static Builder|BudgetLimit whereId($value)
 * @method static Builder|BudgetLimit wherePeriod($value)
 * @method static Builder|BudgetLimit whereStartDate($value)
 * @method static Builder|BudgetLimit whereTransactionCurrencyId($value)
 * @method static Builder|BudgetLimit whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class BudgetLimit extends Model
{
    use ReturnsIntegerIdTrait;

    protected $casts
        = [
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
            'start_date'  => 'date',
            'end_date'    => 'date',
            'auto_budget' => 'boolean',
        ];
    protected $dispatchesEvents
        = [
            'created' => Created::class,
            'updated' => Updated::class,
            'deleted' => Deleted::class,
        ];

    protected $fillable = ['budget_id', 'start_date', 'end_date', 'amount', 'transaction_currency_id'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): self
    {
        if (auth()->check()) {
            $budgetLimitId = (int)$value;
            $budgetLimit   = self::where('budget_limits.id', $budgetLimitId)
                ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                ->where('budgets.user_id', auth()->user()->id)
                ->first(['budget_limits.*'])
            ;
            if (null !== $budgetLimit) {
                return $budgetLimit;
            }
        }

        throw new NotFoundHttpException();
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * Get the amount
     */
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

    protected function transactionCurrencyId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}

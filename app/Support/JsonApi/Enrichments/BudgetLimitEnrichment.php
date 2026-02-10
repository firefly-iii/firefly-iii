<?php

/*
 * BudgetLimitEnrichment.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\OperationsRepository;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BudgetLimitEnrichment implements EnrichmentInterface
{
    private Collection $collection;
    private readonly bool $convertToPrimary; // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    // @phpstan-ignore-line
    private array $currencies  = [];
    private array $currencyIds = [];
    private Carbon $end;
    private array $expenses    = [];
    private array $ids         = [];
    private array $notes       = [];
    private array $pcExpenses  = [];
    private readonly TransactionCurrency $primaryCurrency;
    private Carbon $start;
    private User $user;

    public function __construct()
    {
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
    }

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectCurrencies();
        $this->collectNotes();
        $this->collectBudgets();
        $this->stringifyIds();
        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(array|Model $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection()->push($model);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setUserGroup(UserGroup $userGroup): void {}

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (BudgetLimit $item): BudgetLimit {
            $id         = (int) $item->id;
            $currencyId = (int) $item->transaction_currency_id;
            if (0 === $currencyId) {
                $currencyId = $this->primaryCurrency->id;
            }
            $meta       = [
                'notes'    => $this->notes[$id] ?? null,
                'spent'    => $this->expenses[$id] ?? [],
                'pc_spent' => $this->pcExpenses[$id] ?? [],
                'currency' => $this->currencies[$currencyId],
            ];
            $item->meta = $meta;

            return $item;
        });
    }

    private function collectBudgets(): void
    {
        $budgetIds  = $this->collection
            ->pluck('budget_id')
            ->unique()
            ->toArray()
        ;
        $budgets    = Budget::whereIn('id', $budgetIds)->get();

        $repository = app(OperationsRepository::class);
        $repository->setUser($this->user);
        $expenses   = $repository->collectExpenses($this->start, $this->end, null, $budgets);

        /** @var BudgetLimit $budgetLimit */
        foreach ($this->collection as $budgetLimit) {
            Log::debug(sprintf('Filtering expenses for budget limit #%d (budget #%d)', $budgetLimit->id, $budgetLimit->budget_id));
            $id                  = (int) $budgetLimit->id;
            $filteredExpenses    = $this->filterToBudget($expenses, $budgetLimit->budget_id);
            $filteredExpenses    = $repository->sumCollectedExpenses(
                $filteredExpenses,
                $budgetLimit->start_date,
                $budgetLimit->end_date,
                $budgetLimit->transactionCurrency
            );
            $this->expenses[$id] = array_values($filteredExpenses);

            if ($this->convertToPrimary && $budgetLimit->transactionCurrency->id !== $this->primaryCurrency->id) {
                $pcFilteredExpenses    = $repository->sumCollectedExpenses(
                    $expenses,
                    $budgetLimit->start_date,
                    $budgetLimit->end_date,
                    $budgetLimit->transactionCurrency,
                    true
                );
                $this->pcExpenses[$id] = array_values($pcFilteredExpenses);
            }
            if ($this->convertToPrimary && $budgetLimit->transactionCurrency->id === $this->primaryCurrency->id) {
                $this->pcExpenses[$id] = $this->expenses[$id] ?? [];
            }
        }
    }

    private function collectCurrencies(): void
    {
        $this->currencies[$this->primaryCurrency->id] = $this->primaryCurrency;
        $currencies                                   = TransactionCurrency::whereIn('id', $this->currencyIds)->whereNot(
            'id',
            $this->primaryCurrency->id
        )->get();
        foreach ($currencies as $currency) {
            $this->currencies[(int) $currency->id] = $currency;
        }
    }

    private function collectIds(): void
    {
        $this->start       = $this->collection->min('start_date') ?? Carbon::now()->startOfMonth();
        $this->end         = $this->collection->max('end_date') ?? Carbon::now()->endOfMonth();

        // #11096 make sure that the max end date is also at the end of the day,
        $this->end->endOfDay();

        /** @var BudgetLimit $limit */
        foreach ($this->collection as $limit) {
            $id          = (int) $limit->id;
            $this->ids[] = $id;
            if (0 !== (int) $limit->transaction_currency_id) {
                $this->currencyIds[$id] = (int) $limit->transaction_currency_id;
            }
        }
        $this->ids         = array_unique($this->ids);
        $this->currencyIds = array_unique($this->currencyIds);
    }

    private function collectNotes(): void
    {
        $notes = Note::query()
            ->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', BudgetLimit::class)
            ->get(['notes.noteable_id', 'notes.text'])
            ->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int) $note['noteable_id']] = (string) $note['text'];
        }

        //        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function filterToBudget(array $expenses, int $budget): array
    {
        $result = array_filter($expenses, static fn (array $item): bool => (int) $item['budget_id'] === $budget);
        Log::debug(sprintf('filterToBudget for budget #%d, from %d to %d items', $budget, count($expenses), count($result)));

        return $result;
    }

    private function stringifyIds(): void
    {
        $this->expenses   = array_map(static fn ($first): array => array_map(static function (array $second): array {
            $second['currency_id'] = (string) ($second['currency_id'] ?? 0);

            return $second;
        }, $first), $this->expenses);

        $this->pcExpenses = array_map(static fn (array $first): array => array_map(static function (array $second): array {
            $second['currency_id'] ??= 0;

            return $second;
        }, $first), $this->expenses);
    }
}

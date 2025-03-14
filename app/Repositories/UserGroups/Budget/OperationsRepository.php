<?php

/*
 * OperationsRepository.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\UserGroups\Budget;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;

/**
 * Class OperationsRepository
 *
 * @deprecated
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    use UserGroupTrait;

    /**
     * @throws FireflyException
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $budgets = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUserGroup($this->userGroup)->setRange($start, $end)->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $budgets && $budgets->count() > 0) {
            $collector->setBudgets($budgets);
        }
        if (null === $budgets || (0 === $budgets->count())) {
            $collector->setBudgets($this->getBudgets());
        }
        $collector->withBudgetInformation()->withAccountInformation()->withCategoryInformation();
        $journals  = $collector->getExtractedJournals();
        $array     = [];

        foreach ($journals as $journal) {
            $currencyId                                                                   = (int) $journal['currency_id'];
            $budgetId                                                                     = (int) $journal['budget_id'];
            $budgetName                                                                   = (string) $journal['budget_name'];

            // catch "no budget" entries.
            if (0 === $budgetId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId]                       ??= [
                'budgets'                 => [],
                'currency_id'             => $currencyId,
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
            ];

            // info about the budgets:
            $array[$currencyId]['budgets'][$budgetId] ??= [
                'id'                   => $budgetId,
                'name'                 => $budgetName,
                'transaction_journals' => [],
            ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                    = (int) $journal['transaction_journal_id'];
            $final                                                                        = [
                'amount'                          => app('steam')->negative($journal['amount']),
                'currency_id'                     => $journal['currency_id'],
                'foreign_amount'                  => null,
                'foreign_currency_id'             => null,
                'foreign_currency_code'           => null,
                'foreign_currency_symbol'         => null,
                'foreign_currency_name'           => null,
                'foreign_currency_decimal_places' => null,
                'destination_account_id'          => $journal['destination_account_id'],
                'destination_account_name'        => $journal['destination_account_name'],
                'source_account_id'               => $journal['source_account_id'],
                'source_account_name'             => $journal['source_account_name'],
                'category_name'                   => $journal['category_name'],
                'description'                     => $journal['description'],
                'transaction_group_id'            => $journal['transaction_group_id'],
                'date'                            => $journal['date'],
            ];
            if (null !== $journal['foreign_amount']) {
                $final['foreign_amount']                  = app('steam')->negative($journal['foreign_amount']);
                $final['foreign_currency_id']             = $journal['foreign_currency_id'];
                $final['foreign_currency_code']           = $journal['foreign_currency_code'];
                $final['foreign_currency_symbol']         = $journal['foreign_currency_symbol'];
                $final['foreign_currency_name']           = $journal['foreign_currency_name'];
                $final['foreign_currency_decimal_places'] = $journal['foreign_currency_decimal_places'];
            }

            $array[$currencyId]['budgets'][$budgetId]['transaction_journals'][$journalId] = $final;
        }

        return $array;
    }

    private function getBudgets(): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUserGroup($this->getUserGroup());

        return $repository->getActiveBudgets();
    }
}

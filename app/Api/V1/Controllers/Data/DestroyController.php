<?php

/*
 * DestroyController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Data;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DestroyRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private bool $unused;

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/destroyData
     *
     * @throws FireflyException
     */
    public function destroy(DestroyRequest $request): JsonResponse
    {
        $objects      = $request->getObjects();
        $this->unused = $request->boolean('unused', false);

        $allExceptAssets = [AccountTypeEnum::BENEFICIARY->value, AccountTypeEnum::CASH->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::EXPENSE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::LIABILITY_CREDIT->value, AccountTypeEnum::RECONCILIATION->value, AccountTypeEnum::REVENUE->value];
        $all             = [AccountTypeEnum::ASSET->value, AccountTypeEnum::BENEFICIARY->value, AccountTypeEnum::CASH->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::EXPENSE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::LIABILITY_CREDIT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::RECONCILIATION->value];
        $liabilities     = [AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::CREDITCARD->value];
        $transactions    = [TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value, TransactionTypeEnum::RECONCILIATION->value];

        match ($objects) {
            'budgets'                => $this->destroyBudgets(),
            'bills'                  => $this->destroyBills(),
            'piggy_banks'            => $this->destroyPiggyBanks(),
            'rules'                  => $this->destroyRules(),
            'recurring'              => $this->destroyRecurringTransactions(),
            'categories'             => $this->destroyCategories(),
            'tags'                   => $this->destroyTags(),
            'object_groups'          => $this->destroyObjectGroups(),
            'not_assets_liabilities' => $this->destroyAccounts($allExceptAssets),
            'accounts'               => $this->destroyAccounts($all),
            'asset_accounts'         => $this->destroyAccounts([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value]),
            'expense_accounts'       => $this->destroyAccounts([AccountTypeEnum::BENEFICIARY->value, AccountTypeEnum::EXPENSE->value]),
            'revenue_accounts'       => $this->destroyAccounts([AccountTypeEnum::REVENUE->value]),
            'liabilities'            => $this->destroyAccounts($liabilities),
            'transactions'           => $this->destroyTransactions($transactions),
            'withdrawals'            => $this->destroyTransactions([TransactionTypeEnum::WITHDRAWAL->value]),
            'deposits'               => $this->destroyTransactions([TransactionTypeEnum::DEPOSIT->value]),
            'transfers'              => $this->destroyTransactions([TransactionTypeEnum::TRANSFER->value]),
            default                  => throw new FireflyException(sprintf('200033: This endpoint can\'t handle object "%s"', $objects)),
        };

        app('preferences')->mark();

        return response()->json([], 204);
    }

    private function destroyBudgets(): void
    {
        /** @var AvailableBudgetRepositoryInterface $abRepository */
        $abRepository = app(AvailableBudgetRepositoryInterface::class);
        $abRepository->destroyAll();

        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);
        $blRepository->destroyAll();

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgetRepository->destroyAll();
    }

    private function destroyBills(): void
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->destroyAll();
    }

    private function destroyPiggyBanks(): void
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->destroyAll();
    }

    private function destroyRules(): void
    {
        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);
        $repository->destroyAll();
    }

    private function destroyRecurringTransactions(): void
    {
        /** @var RecurringRepositoryInterface $repository */
        $repository = app(RecurringRepositoryInterface::class);
        $repository->destroyAll();
    }

    private function destroyCategories(): void
    {
        /** @var CategoryRepositoryInterface $categoryRepos */
        $categoryRepos = app(CategoryRepositoryInterface::class);
        $categoryRepos->destroyAll();
    }

    private function destroyTags(): void
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);
        $tagRepository->destroyAll();
    }

    private function destroyObjectGroups(): void
    {
        /** @var ObjectGroupRepositoryInterface $repository */
        $repository = app(ObjectGroupRepositoryInterface::class);
        $repository->deleteAll();
    }

    /**
     * @param array<int, string> $types
     */
    private function destroyAccounts(array $types): void
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $collection = $repository->getAccountsByType($types);
        $service    = app(AccountDestroyService::class);

        /** @var Account $account */
        foreach ($collection as $account) {
            $count = $account->transactions()->count();
            if (true === $this->unused && 0 === $count) {
                app('log')->info(sprintf('Deleted unused account #%d "%s"', $account->id, $account->name));
                Log::channel('audit')->info(sprintf('Deleted unused account #%d "%s"', $account->id, $account->name));
                $service->destroy($account, null);

                continue;
            }
            if (false === $this->unused) {
                app('log')->info(sprintf('Deleting account #%d "%s"', $account->id, $account->name));
                Log::channel('audit')->warning(sprintf('Deleted account #%d "%s"', $account->id, $account->name));
                $service->destroy($account, null);
            }
        }
    }

    /**
     * @param array<int, string> $types
     */
    private function destroyTransactions(array $types): void
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $journals   = $repository->findByType($types);
        $service    = app(JournalDestroyService::class);

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $service->destroy($journal);
        }
    }
}

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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
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
     * @param DestroyRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function destroy(DestroyRequest $request): JsonResponse
    {
        $objects      = $request->getObjects();
        $this->unused = $request->boolean('unused', false);
        switch ($objects) {
            default:
                throw new FireflyException(sprintf('200033: This endpoint can\'t handle object "%s"', $objects));
            case 'budgets':
                $this->destroyBudgets();
                break;
            case 'bills':
                $this->destroyBills();
                break;
            case 'piggy_banks':
                $this->destroyPiggyBanks();
                break;
            case 'rules':
                $this->destroyRules();
                break;
            case 'recurring':
                $this->destroyRecurringTransactions();
                break;
            case 'categories':
                $this->destroyCategories();
                break;
            case 'tags':
                $this->destroyTags();
                break;
            case 'object_groups':
                $this->destroyObjectGroups();
                break;
            case 'not_assets_liabilities':
                $this->destroyAccounts(
                    [
                        AccountType::BENEFICIARY,
                        AccountType::CASH,
                        AccountType::CREDITCARD,
                        AccountType::DEFAULT,
                        AccountType::EXPENSE,
                        AccountType::IMPORT,
                        AccountType::INITIAL_BALANCE,
                        AccountType::LIABILITY_CREDIT,
                        AccountType::RECONCILIATION,
                        AccountType::REVENUE,
                    ]
                );
                break;
            case 'accounts':
                $this->destroyAccounts(
                    [
                        AccountType::ASSET,
                        AccountType::BENEFICIARY,
                        AccountType::CASH,
                        AccountType::CREDITCARD,
                        AccountType::DEBT,
                        AccountType::DEFAULT,
                        AccountType::EXPENSE,
                        AccountType::IMPORT,
                        AccountType::INITIAL_BALANCE,
                        AccountType::LIABILITY_CREDIT,
                        AccountType::LOAN,
                        AccountType::MORTGAGE,
                        AccountType::RECONCILIATION,
                        AccountType::REVENUE,
                    ]
                );
                break;
            case 'asset_accounts':
                $this->destroyAccounts(
                    [
                        AccountType::ASSET,
                        AccountType::DEFAULT,
                    ]
                );
                break;
            case 'expense_accounts':
                $this->destroyAccounts(
                    [
                        AccountType::BENEFICIARY,
                        AccountType::EXPENSE,
                    ]
                );
                break;
            case 'revenue_accounts':
                $this->destroyAccounts(
                    [
                        AccountType::REVENUE,
                    ]
                );
                break;
            case 'liabilities':
                $this->destroyAccounts(
                    [
                        AccountType::DEBT,
                        AccountType::LOAN,
                        AccountType::MORTGAGE,
                        AccountType::CREDITCARD,
                    ]
                );
                break;
            case 'transactions':
                $this->destroyTransactions(
                    [
                        TransactionType::WITHDRAWAL,
                        TransactionType::DEPOSIT,
                        TransactionType::TRANSFER,
                        TransactionType::RECONCILIATION,
                        TransactionType::OPENING_BALANCE,
                    ]
                );
                break;
            case 'withdrawals':
                $this->destroyTransactions(
                    [
                        TransactionType::WITHDRAWAL,
                    ]
                );
                break;
            case 'deposits':
                $this->destroyTransactions(
                    [
                        TransactionType::DEPOSIT,
                    ]
                );
                break;
            case 'transfers':
                $this->destroyTransactions(
                    [
                        TransactionType::TRANSFER,
                    ]
                );
                break;
        }
        app('preferences')->mark();

        return response()->json([], 204);
    }

    /**
     *
     */
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

    /**
     *
     */
    private function destroyBills(): void
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->destroyAll();
    }

    /**
     *
     */
    private function destroyPiggyBanks(): void
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->destroyAll();
    }

    /**
     *
     */
    private function destroyRules(): void
    {
        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);
        $repository->destroyAll();
    }

    /**
     *
     */
    private function destroyRecurringTransactions(): void
    {
        /** @var RecurringRepositoryInterface $repository */
        $repository = app(RecurringRepositoryInterface::class);
        $repository->destroyAll();
    }

    /**
     *
     */
    private function destroyCategories(): void
    {
        /** @var CategoryRepositoryInterface $categoryRepos */
        $categoryRepos = app(CategoryRepositoryInterface::class);
        $categoryRepos->destroyAll();
    }

    /**
     *
     */
    private function destroyTags(): void
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);
        $tagRepository->destroyAll();
    }

    /**
     * @return void
     */
    private function destroyObjectGroups(): void
    {
        /** @var ObjectGroupRepositoryInterface $repository */
        $repository = app(ObjectGroupRepositoryInterface::class);
        $repository->deleteAll();
    }

    /**
     * @param array $types
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
                Log::info(sprintf('Deleted unused account #%d "%s"', $account->id, $account->name));
                $service->destroy($account, null);
                continue;
            }
            if (false === $this->unused) {
                Log::info(sprintf('Deleting account #%d "%s"', $account->id, $account->name));
                $service->destroy($account, null);
            }
        }
    }

    /**
     * @param array $types
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

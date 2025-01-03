<?php

/*
 * GenericRequest.php
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

namespace FireflyIII\Api\V1\Requests\Insight;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * Class GenericRequest
 */
class GenericRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    private Collection $accounts;
    private Collection $bills;
    private Collection $budgets;
    private Collection $categories;
    private Collection $tags;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        return [
            'start' => $this->getCarbonDate('start'),
            'end'   => $this->getCarbonDate('end'),
        ];
    }

    public function getAssetAccounts(): Collection
    {
        $this->parseAccounts();
        $return = new Collection();

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $type = $account->accountType->type;
            if (in_array($type, [AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value], true)) {
                $return->push($account);
            }
        }

        return $return;
    }

    private function parseAccounts(): void
    {
        if (0 !== $this->accounts->count()) {
            return;
        }
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array      = $this->get('accounts');
        if (is_array($array)) {
            foreach ($array as $accountId) {
                $accountId = (int) $accountId;
                $account   = $repository->find($accountId);
                if (null !== $account) {
                    $this->accounts->push($account);
                }
            }
        }
    }

    public function getBills(): Collection
    {
        $this->parseBills();

        return $this->bills;
    }

    private function parseBills(): void
    {
        if (0 !== $this->bills->count()) {
            return;
        }
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array      = $this->get('bills');
        if (is_array($array)) {
            foreach ($array as $billId) {
                $billId = (int) $billId;
                $bill   = $repository->find($billId);
                if (null !== $bill) {
                    $this->bills->push($bill);
                }
            }
        }
    }

    public function getBudgets(): Collection
    {
        $this->parseBudgets();

        return $this->budgets;
    }

    private function parseBudgets(): void
    {
        if (0 !== $this->budgets->count()) {
            return;
        }
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array      = $this->get('budgets');
        if (is_array($array)) {
            foreach ($array as $budgetId) {
                $budgetId = (int) $budgetId;
                $budget   = $repository->find($budgetId);
                if (null !== $budget) {
                    $this->budgets->push($budget);
                }
            }
        }
    }

    public function getCategories(): Collection
    {
        $this->parseCategories();

        return $this->categories;
    }

    private function parseCategories(): void
    {
        if (0 !== $this->categories->count()) {
            return;
        }
        $repository = app(CategoryRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array      = $this->get('categories');
        if (is_array($array)) {
            foreach ($array as $categoryId) {
                $categoryId = (int) $categoryId;
                $category   = $repository->find($categoryId);
                if (null !== $category) {
                    $this->categories->push($category);
                }
            }
        }
    }

    public function getEnd(): Carbon
    {
        $date = $this->getCarbonDate('end');
        $date->endOfDay();

        return $date;
    }

    public function getExpenseAccounts(): Collection
    {
        $this->parseAccounts();
        $return = new Collection();

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $type = $account->accountType->type;
            if (AccountTypeEnum::EXPENSE->value === $type) {
                $return->push($account);
            }
        }

        return $return;
    }

    public function getRevenueAccounts(): Collection
    {
        $this->parseAccounts();
        $return = new Collection();

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $type = $account->accountType->type;
            if (AccountTypeEnum::REVENUE->value === $type) {
                $return->push($account);
            }
        }

        return $return;
    }

    public function getStart(): Carbon
    {
        $date = $this->getCarbonDate('start');
        $date->startOfDay();

        return $date;
    }

    public function getTags(): Collection
    {
        $this->parseTags();

        return $this->tags;
    }

    private function parseTags(): void
    {
        if (0 !== $this->tags->count()) {
            return;
        }
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $array      = $this->get('tags');
        if (is_array($array)) {
            foreach ($array as $tagId) {
                $tagId = (int) $tagId;
                $tag   = $repository->find($tagId);
                if (null !== $tag) {
                    $this->tags->push($tag);
                }
            }
        }
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        // this is cheating, but it works to initialize the collections.
        $this->accounts   = new Collection();
        $this->budgets    = new Collection();
        $this->categories = new Collection();
        $this->bills      = new Collection();
        $this->tags       = new Collection();

        return [
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ];
    }
}

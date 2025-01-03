<?php

/**
 * IsValidAttachmentModel.php
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

namespace FireflyIII\Rules;

use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class IsValidAttachmentModel
 */
class IsValidAttachmentModel implements ValidationRule
{
    private string $model;

    /**
     * IsValidAttachmentModel constructor.
     */
    public function __construct(string $model)
    {
        $model       = $this->normalizeModel($model);
        $this->model = $model;
    }

    private function normalizeModel(string $model): string
    {
        $search  = ['FireflyIII\Models\\'];
        $replace = '';
        $model   = str_replace($search, $replace, $model);

        return sprintf('FireflyIII\Models\%s', $model);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!auth()->check()) {
            $fail('validation.model_id_invalid')->translate();

            return;
        }
        $result = match ($this->model) {
            Account::class            => $this->validateAccount((int) $value),
            Bill::class               => $this->validateBill((int) $value),
            Budget::class             => $this->validateBudget((int) $value),
            Category::class           => $this->validateCategory((int) $value),
            PiggyBank::class          => $this->validatePiggyBank((int) $value),
            Tag::class                => $this->validateTag((int) $value),
            Transaction::class        => $this->validateTransaction((int) $value),
            TransactionJournal::class => $this->validateJournal((int) $value),
            default                   => false,
        };

        if (false === $result) {
            $fail('validation.model_id_invalid')->translate();
        }
    }

    private function validateAccount(int $value): bool
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validateBill(int $value): bool
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validateBudget(int $value): bool
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validateCategory(int $value): bool
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validatePiggyBank(int $value): bool
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validateTag(int $value): bool
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }

    private function validateTransaction(int $value): bool
    {
        /** @var JournalAPIRepositoryInterface $repository */
        $repository = app(JournalAPIRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->findTransaction($value);
    }

    private function validateJournal(int $value): bool
    {
        $repository = app(JournalRepositoryInterface::class);
        $repository->setUser(auth()->user());

        return null !== $repository->find($value);
    }
}

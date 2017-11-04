<?php
/**
 * Transaction.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Support\Twig\Extension\Transaction as TransactionExtension;
use Steam;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Transaction
 *
 * @package FireflyIII\Support\Twig
 */
class Transaction extends Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [
            new Twig_SimpleFilter('transactionIcon', [TransactionExtension::class, 'icon'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionDescription', [TransactionExtension::class, 'description']),
            new Twig_SimpleFilter('transactionIsSplit', [TransactionExtension::class, 'isSplit'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionHasAtt', [TransactionExtension::class, 'hasAttachments'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionAmount', [TransactionExtension::class, 'amount'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionArrayAmount', [TransactionExtension::class, 'amountArray'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionBudgets', [TransactionExtension::class, 'budgets'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionCategories', [TransactionExtension::class, 'categories'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionSourceAccount', [TransactionExtension::class, 'sourceAccount'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionDestinationAccount', [TransactionExtension::class, 'destinationAccount'], ['is_safe' => ['html']]),
        ];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            $this->transactionIdBudgets(),
            $this->transactionIdCategories(),
        ];

        return $functions;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'transaction';
    }


    /**
     * @return Twig_SimpleFunction
     */
    public function transactionIdBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionIdBudgets', function (int $transactionId): string {
            $transaction = TransactionModel::find($transactionId);

            return $this->getTransactionBudgets($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionIdCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionIdCategories', function (int $transactionId): string {
            $transaction = TransactionModel::find($transactionId);

            return $this->getTransactionCategories($transaction);
        }, ['is_safe' => ['html']]
        );
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    private function getTransactionBudgets(TransactionModel $transaction): string
    {
        // journal has a budget:
        if (isset($transaction->transaction_journal_budget_id)) {
            $name = Steam::decrypt(intval($transaction->transaction_journal_budget_encrypted), $transaction->transaction_journal_budget_name);

            return sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_journal_budget_id]), $name, $name);
        }

        // transaction has a budget
        if (isset($transaction->transaction_budget_id)) {
            $name = Steam::decrypt(intval($transaction->transaction_budget_encrypted), $transaction->transaction_budget_name);

            return sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$transaction->transaction_budget_id]), $name, $name);
        }

        // see if the transaction has a budget:
        $budgets = $transaction->budgets()->get();
        if ($budgets->count() === 0) {
            $budgets = $transaction->transactionJournal()->first()->budgets()->get();
        }
        if ($budgets->count() > 0) {
            $str = [];
            foreach ($budgets as $budget) {
                $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('budgets.show', [$budget->id]), $budget->name, $budget->name);
            }

            return join(', ', $str);
        }


        return '';
    }

    /**
     * @param TransactionModel $transaction
     *
     * @return string
     */
    private function getTransactionCategories(TransactionModel $transaction): string
    {
        // journal has a category:
        if (isset($transaction->transaction_journal_category_id)) {
            $name = Steam::decrypt(intval($transaction->transaction_journal_category_encrypted), $transaction->transaction_journal_category_name);

            return sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_journal_category_id]), $name, $name);
        }

        // transaction has a category:
        if (isset($transaction->transaction_category_id)) {
            $name = Steam::decrypt(intval($transaction->transaction_category_encrypted), $transaction->transaction_category_name);

            return sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$transaction->transaction_category_id]), $name, $name);
        }

        // see if the transaction has a category:
        $categories = $transaction->categories()->get();
        if ($categories->count() === 0) {
            $categories = $transaction->transactionJournal()->first()->categories()->get();
        }
        if ($categories->count() > 0) {
            $str = [];
            foreach ($categories as $category) {
                $str[] = sprintf('<a href="%s" title="%s">%s</a>', route('categories.show', [$category->id]), $category->name, $category->name);
            }

            return join(', ', $str);
        }

        return '';
    }
}

<?php
/**
 * Journal.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Twig;


use Amount;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget as ModelBudget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Journal
 *
 * @package FireflyIII\Support\Twig
 */
class Journal extends Twig_Extension
{

    /**
     * @return Twig_SimpleFunction
     */
    public function formatAccountPerspective(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatAccountPerspective', function (TransactionJournal $journal, Account $account) {

            $cache = new CacheProperties;
            $cache->addProperty('formatAccountPerspective');
            $cache->addProperty($journal->id);
            $cache->addProperty($account->id);

            if ($cache->has()) {
                return $cache->get();
            }

            // get the account amount:
            $transactions = $journal->transactions()->where('transactions.account_id', $account->id)->get(['transactions.*']);
            $amount       = '0';
            foreach ($transactions as $transaction) {
                $amount = bcadd($amount, strval($transaction->amount));
            }
            if ($journal->isTransfer()) {
                $amount = bcmul($amount, '-1');
            }

            // check if this sum is the same as the journal:
            $journalSum = TransactionJournal::amount($journal);
            $full       = Amount::formatJournal($journal);
            if (bccomp($journalSum, $amount) === 0 || bccomp(bcmul($journalSum, '-1'), $amount) === 0) {
                $cache->store($full);

                return $full;
            }

            $formatted = Amount::format($amount, true);

            if ($journal->isTransfer()) {
                $formatted = '<span class="text-info">' . Amount::format($amount) . '</span>';
            }
            $str = $formatted . ' (' . $full . ')';
            $cache->store($str);

            return $str;

        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function formatBudgetPerspective(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'formatBudgetPerspective', function (TransactionJournal $journal, ModelBudget $budget) {

            $cache = new CacheProperties;
            $cache->addProperty('formatBudgetPerspective');
            $cache->addProperty($journal->id);
            $cache->addProperty($budget->id);

            if ($cache->has()) {
                return $cache->get();
            }

            // get the account amount:
            $transactions = $journal->transactions()->where('transactions.amount', '<', 0)->get(['transactions.*']);
            $amount       = '0';
            foreach ($transactions as $transaction) {
                $currentBudget = $transaction->budgets->first();
                if (!is_null($currentBudget) && $currentBudget->id === $budget->id) {
                    $amount = bcadd($amount, strval($transaction->amount));
                }
            }
            if ($amount === '0') {
                $formatted = Amount::formatJournal($journal);
                $cache->store($formatted);

                return $formatted;
            }

            $formatted = Amount::format($amount, true) . ' (' . Amount::formatJournal($journal) . ')';
            $cache->store($formatted);

            return $formatted;
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function getDestinationAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'destinationAccount', function (TransactionJournal $journal) {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('destination-account-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $list  = TransactionJournal::destinationAccountList($journal);
            $array = [];
            /** @var Account $entry */
            foreach ($list as $entry) {
                if ($entry->accountType->type == 'Cash account') {
                    $array[] = '<span class="text-success">(cash)</span>';
                    continue;
                }
                $array[] = '<a title="' . e($entry->name) . '" href="' . route('accounts.show', $entry->id) . '">' . e($entry->name) . '</a>';
            }
            $array  = array_unique($array);
            $result = join(', ', $array);
            $cache->store($result);

            return $result;

        }
        );
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [
            $this->typeIcon(),
        ];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            $this->getSourceAccount(),
            $this->getDestinationAccount(),
            $this->formatAccountPerspective(),
            $this->formatBudgetPerspective(),
            $this->journalBudgets(),
            $this->journalCategories(),
            $this->transactionBudgets(),
            $this->transactionCategories(),
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
        return 'FireflyIII\Support\Twig\Journals';
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function getSourceAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'sourceAccount', function (TransactionJournal $journal): string {

            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('source-account-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $list  = TransactionJournal::sourceAccountList($journal);
            $array = [];
            /** @var Account $entry */
            foreach ($list as $entry) {
                if ($entry->accountType->type == 'Cash account') {
                    $array[] = '<span class="text-success">(cash)</span>';
                    continue;
                }
                $array[] = '<a title="' . e($entry->name) . '" href="' . route('accounts.show', $entry->id) . '">' . e($entry->name) . '</a>';
            }
            $array  = array_unique($array);
            $result = join(', ', $array);
            $cache->store($result);

            return $result;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalBudgets', function (TransactionJournal $journal): string {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('budget-string');
            if ($cache->has()) {
                return $cache->get();
            }


            $budgets = [];
            // get all budgets:
            foreach ($journal->budgets as $budget) {
                $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
            }
            // and more!
            foreach ($journal->transactions as $transaction) {
                foreach ($transaction->budgets as $budget) {
                    $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
                }
            }
            $string = join(', ', array_unique($budgets));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalCategories', function (TransactionJournal $journal): string {
            $cache = new CacheProperties;
            $cache->addProperty($journal->id);
            $cache->addProperty('transaction-journal');
            $cache->addProperty('category-string');
            if ($cache->has()) {
                return $cache->get();
            }
            $categories = [];
            // get all categories for the journal itself (easy):
            foreach ($journal->categories as $category) {
                $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name) . '</a>';
            }
            if (count($categories) === 0) {
                $set = Category::distinct()->leftJoin('category_transaction', 'categories.id', '=', 'category_transaction.category_id')
                               ->leftJoin('transactions', 'category_transaction.transaction_id', '=', 'transactions.id')
                               ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                               ->where('categories.user_id', $journal->user_id)
                               ->where('transaction_journals.id', $journal->id)
                               ->get(['categories.*']);
                /** @var Category $category */
                foreach ($set as $category) {
                    $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name)
                                    . '</a>';
                }
            }

            $string = join(', ', array_unique($categories));
            $cache->store($string);

            return $string;
        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionBudgets', function (Transaction $transaction): string {
            $cache = new CacheProperties;
            $cache->addProperty($transaction->id);
            $cache->addProperty('transaction');
            $cache->addProperty('budget-string');
            if ($cache->has()) {
                return $cache->get();
            }

            $budgets = [];
            // get all budgets:
            foreach ($transaction->budgets as $budget) {
                $budgets[] = '<a href="' . route('budgets.show', [$budget->id]) . '" title="' . e($budget->name) . '">' . e($budget->name) . '</a>';
            }
            $string = join(', ', array_unique($budgets));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function transactionCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'transactionCategories', function (Transaction $transaction): string {
            $cache = new CacheProperties;
            $cache->addProperty($transaction->id);
            $cache->addProperty('transaction');
            $cache->addProperty('category-string');
            if ($cache->has()) {
                return $cache->get();
            }


            $categories = [];
            // get all budgets:
            foreach ($transaction->categories as $category) {
                $categories[] = '<a href="' . route('categories.show', [$category->id]) . '" title="' . e($category->name) . '">' . e($category->name) . '</a>';
            }

            $string = join(', ', array_unique($categories));
            $cache->store($string);

            return $string;


        }
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's 5.
     *
     * @return Twig_SimpleFilter
     */
    protected function typeIcon(): Twig_SimpleFilter
    {
        return new Twig_SimpleFilter(
            'typeIcon', function (TransactionJournal $journal): string {

            switch (true) {
                case $journal->isWithdrawal():
                    $txt = '<i class="fa fa-long-arrow-left fa-fw" title="' . trans('firefly.withdrawal') . '"></i>';
                    break;
                case $journal->isDeposit():
                    $txt = '<i class="fa fa-long-arrow-right fa-fw" title="' . trans('firefly.deposit') . '"></i>';
                    break;
                case $journal->isTransfer():
                    $txt = '<i class="fa fa-fw fa-exchange" title="' . trans('firefly.transfer') . '"></i>';
                    break;
                case $journal->isOpeningBalance():
                    $txt = '<i class="fa-fw fa fa-ban" title="' . trans('firefly.openingBalance') . '"></i>';
                    break;
                default:
                    $txt = '';
                    break;
            }

            return $txt;
        }, ['is_safe' => ['html']]
        );
    }

}

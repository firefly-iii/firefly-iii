<?php
/**
 * JournalExportCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Collector;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Transaction;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class JournalExportCollector
 *
 * @package FireflyIII\Export\Collector
 */
class JournalExportCollector extends BasicCollector implements CollectorInterface
{
    /** @var  Collection */
    private $accounts;
    /** @var  Carbon */
    private $end;
    /** @var  Carbon */
    private $start;

    /** @var  Collection */
    private $workSet;

    /**
     * @return bool
     */
    public function run(): bool
    {
        /*
         * Instead of collecting journals we collect transactions for the given accounts.
         * We left join the OPPOSING transaction AND some journal data.
         * After that we complement this info with budgets, categories, etc.
         *
         * This is way more efficient and will also work on split journals.
         */
        $this->getWorkSet();

        /*
         * Extract:
         * possible budget ids for journals
         * possible category ids journals
         * possible budget ids for transactions
         * possible category ids for transactions
         *
         * possible IBAN and account numbers?
         *
         */
        $journals     = $this->extractJournalIds();
        $transactions = $this->extractTransactionIds();


        // extend work set with category data from journals:
        $this->categoryDataForJournals($journals);

        // extend work set with category cate from transactions (overrules journals):
        $this->categoryDataForTransactions($transactions);

        // same for budgets:
        $this->budgetDataForJournals($journals);
        $this->budgetDataForTransactions($transactions);

        $this->setEntries($this->workSet);

        return true;
    }

    /**
     * @param Collection $accounts
     */
    public function setAccounts(Collection $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     */
    public function setDates(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param array $journals
     *
     * @return bool
     */
    private function budgetDataForJournals(array $journals): bool
    {
        $set = DB::table('budget_transaction_journal')
                 ->leftJoin('budgets', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
                 ->whereIn('budget_transaction_journal.transaction_journal_id', $journals)
                 ->get(
                     [
                         'budget_transaction_journal.budget_id',
                         'budget_transaction_journal.transaction_journal_id',
                         'budgets.name',
                         'budgets.encrypted',
                     ]
                 );
        $set->each(
            function ($obj) {
                $obj->name = Steam::decrypt(intval($obj->encrypted), $obj->name);
            }
        );
        $array = [];
        foreach ($set as $obj) {
            $array[$obj->transaction_journal_id] = ['id' => $obj->budget_id, 'name' => $obj->name];
        }

        $this->workSet->each(
            function ($obj) use ($array) {
                if (isset($array[$obj->transaction_journal_id])) {
                    $obj->budget_id   = $array[$obj->transaction_journal_id]['id'];
                    $obj->budget_name = $array[$obj->transaction_journal_id]['name'];
                }
            }
        );

        return true;

    }

    /**
     * @param array $transactions
     *
     * @return bool
     */
    private function budgetDataForTransactions(array $transactions): bool
    {
        $set = DB::table('budget_transaction')
                 ->leftJoin('budgets', 'budgets.id', '=', 'budget_transaction.budget_id')
                 ->whereIn('budget_transaction.transaction_id', $transactions)
                 ->get(
                     [
                         'budget_transaction.budget_id',
                         'budget_transaction.transaction_id',
                         'budgets.name',
                         'budgets.encrypted',
                     ]
                 );
        $set->each(
            function ($obj) {
                $obj->name = Steam::decrypt(intval($obj->encrypted), $obj->name);
            }
        );
        $array = [];
        foreach ($set as $obj) {
            $array[$obj->transaction_id] = ['id' => $obj->budget_id, 'name' => $obj->name];
        }

        $this->workSet->each(
            function ($obj) use ($array) {

                // first transaction
                if (isset($array[$obj->id])) {
                    $obj->budget_id   = $array[$obj->id]['id'];
                    $obj->budget_name = $array[$obj->id]['name'];
                }
            }
        );

        return true;

    }

    /**
     * @param array $journals
     *
     * @return bool
     */
    private function categoryDataForJournals(array $journals): bool
    {
        $set = DB::table('category_transaction_journal')
                 ->leftJoin('categories', 'categories.id', '=', 'category_transaction_journal.category_id')
                 ->whereIn('category_transaction_journal.transaction_journal_id', $journals)
                 ->get(
                     [
                         'category_transaction_journal.category_id',
                         'category_transaction_journal.transaction_journal_id',
                         'categories.name',
                         'categories.encrypted',
                     ]
                 );
        $set->each(
            function ($obj) {
                $obj->name = Steam::decrypt(intval($obj->encrypted), $obj->name);
            }
        );
        $array = [];
        foreach ($set as $obj) {
            $array[$obj->transaction_journal_id] = ['id' => $obj->category_id, 'name' => $obj->name];
        }

        $this->workSet->each(
            function ($obj) use ($array) {
                if (isset($array[$obj->transaction_journal_id])) {
                    $obj->category_id   = $array[$obj->transaction_journal_id]['id'];
                    $obj->category_name = $array[$obj->transaction_journal_id]['name'];
                }
            }
        );

        return true;

    }

    /**
     * @param array $transactions
     *
     * @return bool
     */
    private function categoryDataForTransactions(array $transactions): bool
    {
        $set = DB::table('category_transaction')
                 ->leftJoin('categories', 'categories.id', '=', 'category_transaction.category_id')
                 ->whereIn('category_transaction.transaction_id', $transactions)
                 ->get(
                     [
                         'category_transaction.category_id',
                         'category_transaction.transaction_id',
                         'categories.name',
                         'categories.encrypted',
                     ]
                 );
        $set->each(
            function ($obj) {
                $obj->name = Steam::decrypt(intval($obj->encrypted), $obj->name);
            }
        );
        $array = [];
        foreach ($set as $obj) {
            $array[$obj->transaction_id] = ['id' => $obj->category_id, 'name' => $obj->name];
        }

        $this->workSet->each(
            function ($obj) use ($array) {

                // first transaction
                if (isset($array[$obj->id])) {
                    $obj->category_id   = $array[$obj->id]['id'];
                    $obj->category_name = $array[$obj->id]['name'];
                }
            }
        );

        return true;

    }

    /**
     * @return array
     */
    private function extractJournalIds(): array
    {
        return $this->workSet->pluck('transaction_journal_id')->toArray();
    }

    /**
     * @return array
     */
    private function extractTransactionIds()
    {
        $set      = $this->workSet->pluck('id')->toArray();
        $opposing = $this->workSet->pluck('opposing_id')->toArray();
        $complete = $set + $opposing;

        return array_unique($complete);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getWorkSet()
    {
        $accountIds    = $this->accounts->pluck('id')->toArray();
        $this->workSet = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                    ->leftJoin(
                                        'transactions AS opposing', function (JoinClause $join) {
                                        $join->on('opposing.transaction_journal_id', '=', 'transactions.transaction_journal_id')
                                             ->where('opposing.amount', '=', DB::raw('transactions.amount * -1'))
                                             ->where('transactions.identifier', '=', DB::raw('opposing.identifier'));
                                    }
                                    )
                                    ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                    ->leftJoin('accounts AS opposing_accounts', 'opposing.account_id', '=', 'opposing_accounts.id')
                                    ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', 'transaction_types.id')
                                    ->leftJoin('transaction_currencies', 'transaction_journals.transaction_currency_id', '=', 'transaction_currencies.id')
                                    ->whereIn('transactions.account_id', $accountIds)
                                    ->where('transaction_journals.user_id', $this->job->user_id)
                                    ->where('transaction_journals.date', '>=', $this->start->format('Y-m-d'))
                                    ->where('transaction_journals.date', '<=', $this->end->format('Y-m-d'))
                                    ->where('transaction_journals.completed', 1)
                                    ->whereNull('transaction_journals.deleted_at')
                                    ->whereNull('transactions.deleted_at')
                                    ->whereNull('opposing.deleted_at')
                                    ->orderBy('transaction_journals.date', 'DESC')
                                    ->orderBy('transactions.identifier', 'ASC')
                                    ->get(
                                        [
                                            'transactions.id',
                                            'transactions.amount',
                                            'transactions.description',
                                            'transactions.account_id',
                                            'accounts.name as account_name',
                                            'accounts.encrypted as account_name_encrypted',
                                            'transactions.identifier',

                                            'opposing.id as opposing_id',
                                            'opposing.amount AS opposing_amount',
                                            'opposing.description as opposing_description',
                                            'opposing.account_id as opposing_account_id',
                                            'opposing_accounts.name as opposing_account_name',
                                            'opposing_accounts.encrypted as opposing_account_encrypted',
                                            'opposing.identifier as opposing_identifier',

                                            'transaction_journals.id as transaction_journal_id',
                                            'transaction_journals.date',
                                            'transaction_journals.description as journal_description',
                                            'transaction_journals.encrypted as journal_encrypted',
                                            'transaction_journals.transaction_type_id',
                                            'transaction_types.type as transaction_type',
                                            'transaction_journals.transaction_currency_id',
                                            'transaction_currencies.code AS transaction_currency_code',

                                        ]
                                    );
    }
}

<?php
/**
 * JournalTasker.php
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

namespace FireflyIII\Repositories\Journal;

use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\SingleCacheProperties;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class JournalTasker.
 */
class JournalTasker implements JournalTaskerInterface
{
    /** @var User */
    private $user;

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection
    {
        /** @var Collection $set */
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            function (PiggyBankEvent $event) {
                $event->piggyBank = $event->piggyBank()->withTrashed()->first();
            }
        );

        return $events;
    }

    /**
     * Get an overview of the transactions of a journal, tailored to the view
     * that shows a transaction (transaction/show/xx).
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTransactionsOverview(TransactionJournal $journal): array
    {
        $cache = new SingleCacheProperties;
        $cache->addProperty('transaction-overview');
        $cache->addProperty($journal->id);
        $cache->addProperty($journal->updated_at);
        if ($cache->has()) {
            return $cache->get();
        }
        // get all transaction data + the opposite site in one list.
        $set = $journal
            ->transactions()// "source"
            ->leftJoin(
                'transactions as destination',
                function (JoinClause $join) {
                    $join
                        ->on('transactions.transaction_journal_id', '=', 'destination.transaction_journal_id')
                        ->where('transactions.amount', '=', DB::raw('destination.amount * -1'))
                        ->where('transactions.identifier', '=', DB::raw('destination.identifier'))
                        ->whereNull('destination.deleted_at');
                }
            )
            ->with(['budgets', 'categories'])
            ->leftJoin('accounts as source_accounts', 'transactions.account_id', '=', 'source_accounts.id')
            ->leftJoin('account_types as source_account_types', 'source_accounts.account_type_id', '=', 'source_account_types.id')
            ->leftJoin('accounts as destination_accounts', 'destination.account_id', '=', 'destination_accounts.id')
            ->leftJoin('account_types as destination_account_types', 'destination_accounts.account_type_id', '=', 'destination_account_types.id')
            ->leftJoin('transaction_currencies as native_currencies', 'transactions.transaction_currency_id', '=', 'native_currencies.id')
            ->leftJoin('transaction_currencies as foreign_currencies', 'transactions.foreign_currency_id', '=', 'foreign_currencies.id')
            ->where('transactions.amount', '<', 0)
            ->whereNull('transactions.deleted_at')
            ->get(
                [
                    'transactions.id',
                    'transactions.account_id',
                    'source_accounts.name as account_name',
                    'source_accounts.encrypted as account_encrypted',
                    'source_account_types.type as account_type',
                    'transactions.amount',
                    'transactions.foreign_amount',
                    'transactions.description',
                    'destination.id as destination_id',
                    'destination.account_id as destination_account_id',
                    'destination_accounts.name as destination_account_name',
                    'destination_accounts.encrypted as destination_account_encrypted',
                    'destination_account_types.type as destination_account_type',
                    'native_currencies.id as transaction_currency_id',
                    'native_currencies.decimal_places as transaction_currency_dp',
                    'native_currencies.code as transaction_currency_code',
                    'native_currencies.symbol as transaction_currency_symbol',

                    'foreign_currencies.id as foreign_currency_id',
                    'foreign_currencies.decimal_places as foreign_currency_dp',
                    'foreign_currencies.code as foreign_currency_code',
                    'foreign_currencies.symbol as foreign_currency_symbol',
                ]
            );

        $transactions    = [];
        $transactionType = $journal->transactionType->type;

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            $sourceBalance      = $this->getBalance(intval($entry->id));
            $destinationBalance = $this->getBalance(intval($entry->destination_id));
            $budget             = $entry->budgets->first();
            $category           = $entry->categories->first();
            $transaction        = [
                'journal_type'                => $transactionType,
                'updated_at'                  => $journal->updated_at,
                'source_id'                   => $entry->id,
                'source'                      => $journal->transactions()->find($entry->id),
                'source_amount'               => $entry->amount,
                'foreign_source_amount'       => $entry->foreign_amount,
                'description'                 => $entry->description,
                'source_account_id'           => $entry->account_id,
                'source_account_name'         => Steam::decrypt(intval($entry->account_encrypted), $entry->account_name),
                'source_account_type'         => $entry->account_type,
                'source_account_before'       => $sourceBalance,
                'source_account_after'        => bcadd($sourceBalance, $entry->amount),
                'destination_id'              => $entry->destination_id,
                'destination_amount'          => bcmul($entry->amount, '-1'),
                'foreign_destination_amount'  => null === $entry->foreign_amount ? null : bcmul($entry->foreign_amount, '-1'),
                'destination_account_id'      => $entry->destination_account_id,
                'destination_account_type'    => $entry->destination_account_type,
                'destination_account_name'    => Steam::decrypt(intval($entry->destination_account_encrypted), $entry->destination_account_name),
                'destination_account_before'  => $destinationBalance,
                'destination_account_after'   => bcadd($destinationBalance, bcmul($entry->amount, '-1')),
                'budget_id'                   => null === $budget ? 0 : $budget->id,
                'category'                    => null === $category ? '' : $category->name,
                'transaction_currency_id'     => $entry->transaction_currency_id,
                'transaction_currency_code'   => $entry->transaction_currency_code,
                'transaction_currency_symbol' => $entry->transaction_currency_symbol,
                'transaction_currency_dp'     => $entry->transaction_currency_dp,
                'foreign_currency_id'         => $entry->foreign_currency_id,
                'foreign_currency_code'       => $entry->foreign_currency_code,
                'foreign_currency_symbol'     => $entry->foreign_currency_symbol,
                'foreign_currency_dp'         => $entry->foreign_currency_dp,
            ];
            if (AccountType::CASH === $entry->destination_account_type) {
                $transaction['destination_account_name'] = '';
            }

            if (AccountType::CASH === $entry->account_type) {
                $transaction['source_account_name'] = '';
            }

            $transactions[] = $transaction;
        }
        $cache->store($transactions);

        return $transactions;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Collect the balance of an account before the given transaction has hit. This is tricky, because
     * the balance does not depend on the transaction itself but the journal it's part of. And of course
     * the order of transactions within the journal. So the query is pretty complex:.
     *
     * @param int $transactionId
     *
     * @return string
     */
    private function getBalance(int $transactionId): string
    {
        // find the transaction first:
        $transaction = Transaction::find($transactionId);
        $date        = $transaction->transactionJournal->date->format('Y-m-d');
        $order       = intval($transaction->transactionJournal->order);
        $journalId   = intval($transaction->transaction_journal_id);
        $identifier  = intval($transaction->identifier);

        // also add the virtual balance to the balance:
        $virtualBalance = strval($transaction->account->virtual_balance);

        // go!
        $sum = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->where('account_id', $transaction->account_id)
                          ->whereNull('transactions.deleted_at')
                          ->whereNull('transaction_journals.deleted_at')
                          ->where('transactions.id', '!=', $transactionId)
                          ->where(
                              function (Builder $q1) use ($date, $order, $journalId, $identifier) {
                                  $q1->where('transaction_journals.date', '<', $date); // date
                                  $q1->orWhere(
                                      function (Builder $q2) use ($date, $order) { // function 1
                                          $q2->where('transaction_journals.date', $date);
                                          $q2->where('transaction_journals.order', '>', $order);
                                      }
                                  );
                                  $q1->orWhere(
                                      function (Builder $q3) use ($date, $order, $journalId) { // function 2
                                          $q3->where('transaction_journals.date', $date);
                                          $q3->where('transaction_journals.order', $order);
                                          $q3->where('transaction_journals.id', '<', $journalId);
                                      }
                                  );
                                  $q1->orWhere(
                                      function (Builder $q4) use ($date, $order, $journalId, $identifier) { // function 3
                                          $q4->where('transaction_journals.date', $date);
                                          $q4->where('transaction_journals.order', $order);
                                          $q4->where('transaction_journals.id', $journalId);
                                          $q4->where('transactions.identifier', '>', $identifier);
                                      }
                                  );
                              }
                          )->sum('transactions.amount');

        return bcadd(strval($sum), $virtualBalance);
    }
}

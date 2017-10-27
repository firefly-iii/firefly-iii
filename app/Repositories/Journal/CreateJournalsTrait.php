<?php
/**
 * CreateJournalsTrait.php
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


use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * @property User $user
 *
 * Trait CreateJournalsTrait
 *
 * @package FireflyIII\Repositories\Journal
 */
trait CreateJournalsTrait
{
    /**
     * @param User            $user
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     */
    abstract public function storeAccounts(User $user, TransactionType $type, array $data): array;

    /**
     *
     * * Remember: a balancingAct takes at most one expense and one transfer.
     *            an advancePayment takes at most one expense, infinite deposits and NO transfers.
     *
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    protected function saveTags(TransactionJournal $journal, array $array): bool
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);

        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                if (!is_null($tag)) {
                    Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
                    $tagRepository->connect($journal, $tag);
                }
            }
        }

        return true;
    }

    /**
     * @param Transaction $transaction
     * @param int         $budgetId
     */
    protected function storeBudgetWithTransaction(Transaction $transaction, int $budgetId)
    {
        if (intval($budgetId) > 0 && $transaction->transactionJournal->transactionType->type !== TransactionType::TRANSFER) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($budgetId);
            $transaction->budgets()->save($budget);
        }
    }

    /**
     * @param Transaction $transaction
     * @param string      $category
     */
    protected function storeCategoryWithTransaction(Transaction $transaction, string $category)
    {
        if (strlen($category) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $category, 'user_id' => $transaction->transactionJournal->user_id]);
            $transaction->categories()->save($category);
        }
    }

    /**
     * The reference to storeAccounts() in this function is an indication of spagetti code but alas,
     * I leave it as it is.
     *
     * @param TransactionJournal $journal
     * @param array              $transaction
     * @param int                $identifier
     *
     * @return Collection
     */
    protected function storeSplitTransaction(TransactionJournal $journal, array $transaction, int $identifier): Collection
    {
        // store source and destination accounts (depends on type)
        $accounts = $this->storeAccounts($this->user, $journal->transactionType, $transaction);

        // store transaction one way:
        $amount        = bcmul(strval($transaction['amount']), '-1');
        $foreignAmount = is_null($transaction['foreign_amount']) ? null : bcmul(strval($transaction['foreign_amount']), '-1');
        $one           = $this->storeTransaction(
            [
                'journal'                 => $journal,
                'account'                 => $accounts['source'],
                'amount'                  => $amount,
                'transaction_currency_id' => $transaction['transaction_currency_id'],
                'foreign_amount'          => $foreignAmount,
                'foreign_currency_id'     => $transaction['foreign_currency_id'],
                'description'             => $transaction['description'],
                'category'                => null,
                'budget'                  => null,
                'identifier'              => $identifier,
            ]
        );
        $this->storeCategoryWithTransaction($one, $transaction['category']);
        $this->storeBudgetWithTransaction($one, $transaction['budget_id']);

        // and the other way:
        $amount        = strval($transaction['amount']);
        $foreignAmount = is_null($transaction['foreign_amount']) ? null : strval($transaction['foreign_amount']);
        $two           = $this->storeTransaction(
            [
                'journal'                 => $journal,
                'account'                 => $accounts['destination'],
                'amount'                  => $amount,
                'transaction_currency_id' => $transaction['transaction_currency_id'],
                'foreign_amount'          => $foreignAmount,
                'foreign_currency_id'     => $transaction['foreign_currency_id'],
                'description'             => $transaction['description'],
                'category'                => null,
                'budget'                  => null,
                'identifier'              => $identifier,
            ]
        );
        $this->storeCategoryWithTransaction($two, $transaction['category']);
        $this->storeBudgetWithTransaction($two, $transaction['budget_id']);

        return new Collection([$one, $two]);
    }

    /**
     * @param array $data
     *
     * @return Transaction
     */
    protected function storeTransaction(array $data): Transaction
    {
        $fields = [
            'transaction_journal_id'  => $data['journal']->id,
            'account_id'              => $data['account']->id,
            'amount'                  => $data['amount'],
            'foreign_amount'          => $data['foreign_amount'],
            'transaction_currency_id' => $data['transaction_currency_id'],
            'foreign_currency_id'     => $data['foreign_currency_id'],
            'description'             => $data['description'],
            'identifier'              => $data['identifier'],
        ];


        if (is_null($data['foreign_currency_id'])) {
            unset($fields['foreign_currency_id']);
        }
        if (is_null($data['foreign_amount'])) {
            unset($fields['foreign_amount']);
        }

        /** @var Transaction $transaction */
        $transaction = Transaction::create($fields);

        Log::debug(sprintf('Transaction stored with ID: %s', $transaction->id));

        if (!is_null($data['category'])) {
            $transaction->categories()->save($data['category']);
        }

        if (!is_null($data['budget'])) {
            $transaction->categories()->save($data['budget']);
        }

        return $transaction;

    }


    /**
     * @param TransactionJournal $journal
     * @param string             $note
     *
     * @return bool
     */
    protected function updateNote(TransactionJournal $journal, string $note): bool
    {
        if (strlen($note) === 0) {
            $dbNote = $journal->notes()->first();
            if (!is_null($dbNote)) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote = $journal->notes()->first();
        if (is_null($dbNote)) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($journal);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}

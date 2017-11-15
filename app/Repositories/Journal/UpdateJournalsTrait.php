<?php
/**
 * UpdateJournalsTrait.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Log;

/**
 * Trait UpdateJournalsTrait
 *
 * @package FireflyIII\Repositories\Journal
 */
trait UpdateJournalsTrait
{

    /**
     * When the user edits a split journal, each line is missing crucial data:
     *
     * - Withdrawal lines are missing the source account ID
     * - Deposit lines are missing the destination account ID
     * - Transfers are missing both.
     *
     * We need to append the array.
     *
     * @param array $transaction
     * @param array $data
     *
     * @return array
     */
    protected function appendTransactionData(array $transaction, array $data): array
    {
        switch ($data['what']) {
            case strtolower(TransactionType::TRANSFER):
            case strtolower(TransactionType::WITHDRAWAL):
                $transaction['source_account_id'] = intval($data['journal_source_account_id']);
                break;
        }

        switch ($data['what']) {
            case strtolower(TransactionType::TRANSFER):
            case strtolower(TransactionType::DEPOSIT):
                $transaction['destination_account_id'] = intval($data['journal_destination_account_id']);
                break;
        }

        return $transaction;
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param array              $data
     *
     * @throws FireflyException
     */
    protected function updateDestinationTransaction(TransactionJournal $journal, Account $account, array $data)
    {
        $set = $journal->transactions()->where('amount', '>', 0)->get();
        if ($set->count() !== 1) {
            throw new FireflyException(sprintf('Journal #%d has %d transactions with an amount more than zero.', $journal->id, $set->count()));
        }
        /** @var Transaction $transaction */
        $transaction                          = $set->first();
        $transaction->amount                  = app('steam')->positive($data['amount']);
        $transaction->transaction_currency_id = $data['currency_id'];
        $transaction->foreign_amount          = is_null($data['foreign_amount']) ? null : app('steam')->positive($data['foreign_amount']);
        $transaction->foreign_currency_id     = $data['foreign_currency_id'];
        $transaction->account_id              = $account->id;
        $transaction->save();
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     * @param array              $data
     *
     * @throws FireflyException
     */
    protected function updateSourceTransaction(TransactionJournal $journal, Account $account, array $data)
    {
        // should be one:
        $set = $journal->transactions()->where('amount', '<', 0)->get();
        if ($set->count() !== 1) {
            throw new FireflyException(sprintf('Journal #%d has %d transactions with an amount more than zero.', $journal->id, $set->count()));
        }
        /** @var Transaction $transaction */
        $transaction                          = $set->first();
        $transaction->amount                  = bcmul(app('steam')->positive($data['amount']), '-1');
        $transaction->transaction_currency_id = $data['currency_id'];
        $transaction->foreign_amount          = is_null($data['foreign_amount']) ? null : bcmul(app('steam')->positive($data['foreign_amount']), '-1');
        $transaction->foreign_currency_id     = $data['foreign_currency_id'];
        $transaction->account_id              = $account->id;
        $transaction->save();
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    protected function updateTags(TransactionJournal $journal, array $array): bool
    {
        // create tag repository
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);


        // find or create all tags:
        $tags = [];
        $ids  = [];
        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag    = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                $tags[] = $tag;
                $ids[]  = $tag->id;
            }
        }

        // delete all tags connected to journal not in this array:
        if (count($ids) > 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->whereNotIn('tag_id', $ids)->delete();
        }
        // if count is zero, delete them all:
        if (count($ids) === 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->delete();
        }

        // connect each tag to journal (if not yet connected):
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
            $tagRepository->connect($journal, $tag);
        }

        return true;
    }
}

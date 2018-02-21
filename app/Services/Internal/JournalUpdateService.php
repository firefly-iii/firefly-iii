<?php
/**
 * JournalUpdateService.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Internal;

use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class to centralise code that updates a journal given the input by system.
 *
 * Class JournalUpdateService
 */
class JournalUpdateService
{
    /** @var User */
    private $user;

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {
        // update journal:
        $journal->description = $data['description'];
        $journal->date        = $data['date'];
        $journal->save();

        // update transactions:
        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user);

        // create transactions
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user);

        /**
         * @var int   $identifier
         * @var array $trData
         */
        foreach ($data['transactions'] as $identifier => $trData) {
            // exists transaction(s) with this identifier? update!
            /** @var Collection $existing */
            $existing = $journal->transactions()->where('identifier', $identifier)->get();
            if ($existing->count() > 0) {
                $existing->each(
                    function (Transaction $transaction) use ($service, $trData) {
                        $service->update($transaction, $trData);
                    }
                );
                continue;
            }
            // otherwise, create!
            $factory->createPair($journal, $trData);
        }

        // connect bill:
        $this->connectBill($journal, $data);

        // connect tags:
        $this->connectTags($journal, $data);

        // update or create custom fields:
        // store date meta fields (if present):
        $this->storeMeta($journal, $data, 'interest_date');
        $this->storeMeta($journal, $data, 'book_date');
        $this->storeMeta($journal, $data, 'process_date');
        $this->storeMeta($journal, $data, 'due_date');
        $this->storeMeta($journal, $data, 'payment_date');
        $this->storeMeta($journal, $data, 'invoice_date');
        $this->storeMeta($journal, $data, 'internal_reference');

        // store note:
        $this->storeNote($journal, $data['notes']);


        return $journal;
    }

    /**
     * TODO seems duplicate of connectBill in JournalFactory.
     * TODO this one is better than journal factory
     * Connect bill if present.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectBill(TransactionJournal $journal, array $data): void
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($this->user);
        $bill = $factory->find($data['bill_id'], $data['bill_name']);

        if (!is_null($bill)) {
            $journal->bill_id = $bill->id;
            $journal->save();

            return;
        }
        $journal->bill_id = null;
        $journal->save();

        return;
    }

    /**
     * TODO seems duplicate or very equal to connectTags() in JournalFactory.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectTags(TransactionJournal $journal, array $data): void
    {
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($journal->user);
        $set = [];
        foreach ($data['tags'] as $string) {
            if (strlen($string) > 0) {
                $tag   = $factory->findOrCreate($string);
                $set[] = $tag->id;
            }
        }
        $journal->tags()->sync($set);
    }

    /**
     * TODO seems duplicate of storeMeta() in journalfactory.
     * TODO this one is better than the one in journal factory (NULL)>
     *
     * @param TransactionJournal $journal
     * @param array              $data
     * @param string             $field
     *
     * @throws \Exception
     */
    protected function storeMeta(TransactionJournal $journal, array $data, string $field): void
    {
        $set = [
            'journal' => $journal,
            'name'    => $field,
            'data'    => $data[$field],
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);
    }

    /**
     * TODO is duplicate of storeNote in journal factory.
     *
     * @param TransactionJournal $journal
     * @param string             $notes
     */
    protected function storeNote(TransactionJournal $journal, string $notes): void
    {
        if (strlen($notes) > 0) {
            $note = $journal->notes()->first();
            if (is_null($note)) {
                $note = new Note;
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();

            return;
        }
        $note = $journal->notes()->first();
        if (!is_null($note)) {
            $note->delete();
        }

        return;

    }

}
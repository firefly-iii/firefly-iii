<?php

/**
 * TransactionGroupRepository.php
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

namespace FireflyIII\Repositories\TransactionGroup;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Location;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Update\GroupUpdateService;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class TransactionGroupRepository
 */
class TransactionGroupRepository implements TransactionGroupRepositoryInterface
{
    private User $user;

    public function countAttachments(int $journalId): int
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);

        return $journal->attachments()->count();
    }

    /**
     * Find a transaction group by its ID.
     */
    public function find(int $groupId): ?TransactionGroup
    {
        /** @var null|TransactionGroup */
        return $this->user->transactionGroups()->find($groupId);
    }

    public function destroy(TransactionGroup $group): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $service = new TransactionGroupDestroyService();
        $service->destroy($group);
    }

    public function expandGroup(TransactionGroup $group): array
    {
        $result                         = $group->toArray();
        $result['transaction_journals'] = [];

        /** @var TransactionJournal $journal */
        foreach ($group->transactionJournals as $journal) {
            $result['transaction_journals'][] = $this->expandJournal($journal);
        }

        return $result;
    }

    private function expandJournal(TransactionJournal $journal): array
    {
        $array                      = $journal->toArray();
        $array['transactions']      = [];
        $array['meta']              = $journal->transactionJournalMeta->toArray();
        $array['tags']              = $journal->tags->toArray();
        $array['categories']        = $journal->categories->toArray();
        $array['budgets']           = $journal->budgets->toArray();
        $array['notes']             = $journal->notes->toArray();
        $array['locations']         = [];
        $array['attachments']       = $journal->attachments->toArray();
        $array['links']             = [];
        $array['piggy_bank_events'] = $journal->piggyBankEvents->toArray();

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $array['transactions'][] = $this->expandTransaction($transaction);
        }

        return $array;
    }

    private function expandTransaction(Transaction $transaction): array
    {
        $array               = $transaction->toArray();
        $array['account']    = $transaction->account->toArray();
        $array['budgets']    = [];
        $array['categories'] = [];

        foreach ($transaction->categories as $category) {
            $array['categories'][] = $category->toArray();
        }

        foreach ($transaction->budgets as $budget) {
            $array['budgets'][] = $budget->toArray();
        }

        return $array;
    }

    /**
     * Return all attachments for all journals in the group.
     */
    public function getAttachments(TransactionGroup $group): array
    {
        $repository = app(AttachmentRepositoryInterface::class);
        $repository->setUser($this->user);
        $journals   = $group->transactionJournals->pluck('id')->toArray();
        $set        = Attachment::whereIn('attachable_id', $journals)
            ->where('attachable_type', TransactionJournal::class)
            ->where('uploaded', true)
            ->whereNull('deleted_at')->get()
        ;

        $result     = [];

        /** @var Attachment $attachment */
        foreach ($set as $attachment) {
            $journalId                = $attachment->attachable_id;
            $result[$journalId] ??= [];
            $current                  = $attachment->toArray();
            $current['file_exists']   = true;
            $current['notes']         = $repository->getNoteText($attachment);
            // already determined that this attachable is a TransactionJournal.
            $current['journal_title'] = $attachment->attachable->description;
            $result[$journalId][]     = $current;
        }

        return $result;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Get the note text for a journal (by ID).
     */
    public function getNoteText(int $journalId): ?string
    {
        /** @var null|Note $note */
        $note = Note::where('noteable_id', $journalId)
            ->where('noteable_type', TransactionJournal::class)
            ->first()
        ;
        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * Return all journal links for all journals in the group.
     */
    public function getLinks(TransactionGroup $group): array
    {
        $return   = [];
        $journals = $group->transactionJournals->pluck('id')->toArray();
        $set      = TransactionJournalLink::where(
            static function (Builder $q) use ($journals): void {
                $q->whereIn('source_id', $journals);
                $q->orWhereIn('destination_id', $journals);
            }
        )
            ->with(['source', 'destination', 'source.transactions'])
            ->leftJoin('link_types', 'link_types.id', '=', 'journal_links.link_type_id')
            ->get(['journal_links.*', 'link_types.inward', 'link_types.outward', 'link_types.editable'])
        ;

        /** @var TransactionJournalLink $entry */
        foreach ($set as $entry) {
            $journalId = in_array($entry->source_id, $journals, true) ? $entry->source_id : $entry->destination_id;
            $return[$journalId] ??= [];

            // phpstan: the editable field is provided by the query.

            if ($journalId === $entry->source_id) {
                $amount               = $this->getFormattedAmount($entry->destination);
                $foreignAmount        = $this->getFormattedForeignAmount($entry->destination);
                $return[$journalId][] = [
                    'id'             => $entry->id,
                    'link'           => $entry->outward,
                    'group'          => $entry->destination->transaction_group_id,
                    'description'    => $entry->destination->description,
                    'editable'       => 1 === (int) $entry->editable,
                    'amount'         => $amount,
                    'foreign_amount' => $foreignAmount,
                ];
            }
            if ($journalId === $entry->destination_id) {
                $amount               = $this->getFormattedAmount($entry->source);
                $foreignAmount        = $this->getFormattedForeignAmount($entry->source);
                $return[$journalId][] = [
                    'id'             => $entry->id,
                    'link'           => $entry->inward,
                    'group'          => $entry->source->transaction_group_id,
                    'description'    => $entry->source->description,
                    'editable'       => 1 === (int) $entry->editable,
                    'amount'         => $amount,
                    'foreign_amount' => $foreignAmount,
                ];
            }
        }

        return $return;
    }

    private function getFormattedAmount(TransactionJournal $journal): string
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions->first();
        $currency    = $transaction->transactionCurrency;
        $type        = $journal->transactionType->type;
        $amount      = app('steam')->positive($transaction->amount);
        $return      = '';
        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            $return = app('amount')->formatAnything($currency, app('steam')->negative($amount));
        }
        if (TransactionTypeEnum::WITHDRAWAL->value !== $type) {
            $return = app('amount')->formatAnything($currency, $amount);
        }

        return $return;
    }

    private function getFormattedForeignAmount(TransactionJournal $journal): string
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions->first();
        if (null === $transaction->foreign_amount || '' === $transaction->foreign_amount) {
            return '';
        }
        if (0 === bccomp('0', $transaction->foreign_amount)) {
            return '';
        }
        $currency    = $transaction->foreignCurrency;
        $type        = $journal->transactionType->type;
        $amount      = app('steam')->positive($transaction->foreign_amount);
        $return      = '';
        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            $return = app('amount')->formatAnything($currency, app('steam')->negative($amount));
        }
        if (TransactionTypeEnum::WITHDRAWAL->value !== $type) {
            $return = app('amount')->formatAnything($currency, $amount);
        }

        return $return;
    }

    public function getLocation(int $journalId): ?Location
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);

        /** @var null|Location */
        return $journal->locations()->first();
    }

    /**
     * Return object with all found meta field things as Carbon objects.
     *
     * @throws \Exception
     */
    public function getMetaDateFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = \DB::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data'])
        ;
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = new Carbon(json_decode($row->data, true, 512, JSON_THROW_ON_ERROR));
        }

        return new NullArrayObject($return);
    }

    /**
     * Return object with all found meta field things.
     */
    public function getMetaFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = \DB::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data'])
        ;
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = json_decode($row->data);
        }

        return new NullArrayObject($return);
    }

    /**
     * Return all piggy bank events for all journals in the group.
     *
     * @throws FireflyException
     */
    public function getPiggyEvents(TransactionGroup $group): array
    {
        $return   = [];
        $journals = $group->transactionJournals->pluck('id')->toArray();
        $currency = app('amount')->getNativeCurrencyByUserGroup($this->user->userGroup);
        $data     = PiggyBankEvent::whereIn('transaction_journal_id', $journals)
            ->with('piggyBank', 'piggyBank.account')
            ->get(['piggy_bank_events.*'])
        ;

        /** @var PiggyBankEvent $row */
        foreach ($data as $row) {
            if (null === $row->piggyBank) {
                continue;
            }
            // get currency preference.
            $currencyPreference   = AccountMeta::where('account_id', $row->piggyBank->account_id)
                ->where('name', 'currency_id')
                ->first()
            ;
            if (null !== $currencyPreference) {
                $currency = TransactionCurrency::where('id', $currencyPreference->data)->first();
            }
            $journalId            = $row->transaction_journal_id;
            $return[$journalId] ??= [];

            $return[$journalId][] = [
                'piggy'    => $row->piggyBank->name,
                'piggy_id' => $row->piggy_bank_id,
                'amount'   => app('amount')->formatAnything($currency, $row->amount),
            ];
        }

        return $return;
    }

    public function getTagObjects(int $journalId): Collection
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);

        return $journal->tags()->get();
    }

    /**
     * Get the tags for a journal (by ID).
     */
    public function getTags(int $journalId): array
    {
        $result = \DB::table('tag_transaction_journal')
            ->leftJoin('tags', 'tag_transaction_journal.tag_id', '=', 'tags.id')
            ->where('tag_transaction_journal.transaction_journal_id', $journalId)
            ->orderBy('tags.tag', 'ASC')
            ->get(['tags.tag'])
        ;

        return $result->pluck('tag')->toArray();
    }

    /**
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function store(array $data): TransactionGroup
    {
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($this->user);

        try {
            return $factory->create($data);
        } catch (DuplicateTransactionException $e) {
            app('log')->warning('Group repository caught group factory with a duplicate exception!');

            throw new DuplicateTransactionException($e->getMessage(), 0, $e);
        } catch (FireflyException $e) {
            app('log')->warning('Group repository caught group factory with an exception!');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        /** @var GroupUpdateService $service */
        $service = app(GroupUpdateService::class);

        return $service->update($transactionGroup, $data);
    }
}

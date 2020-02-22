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
use DB;
use Exception;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Update\GroupUpdateService;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Log;

/**
 * Class TransactionGroupRepository
 */
class TransactionGroupRepository implements TransactionGroupRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            app('log')->warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param TransactionGroup $group
     */
    public function destroy(TransactionGroup $group): void
    {
        /** @var TransactionGroupDestroyService $service */
        $service = new TransactionGroupDestroyService;
        $service->destroy($group);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * Find a transaction group by its ID.
     *
     * @param int $groupId
     *
     * @return TransactionGroup|null
     */
    public function find(int $groupId): ?TransactionGroup
    {
        return $this->user->transactionGroups()->where('id', $groupId)->first();
    }

    /**
     * Return all attachments for all journals in the group.
     *
     * @param TransactionGroup $group
     *
     * @return array
     */
    public function getAttachments(TransactionGroup $group): array
    {
        $journals = $group->transactionJournals->pluck('id')->toArray();
        $set      = Attachment::whereIn('attachable_id', $journals)
                              ->where('attachable_type', TransactionJournal::class)
                              ->where('uploaded', 1)
                              ->whereNull('deleted_at')->get();

        $result = [];
        /** @var Attachment $attachment */
        foreach ($set as $attachment) {
            $journalId                = (int)$attachment->attachable_id;
            $result[$journalId]       = $result[$journalId] ?? [];
            $current                  = $attachment->toArray();
            $current['file_exists']   = true;
            $current['journal_title'] = $attachment->attachable->description;
            $result[$journalId][]     = $current;

        }

        return $result;
    }

    /**
     * Return all journal links for all journals in the group.
     *
     * @param TransactionGroup $group
     *
     * @return array
     */
    public function getLinks(TransactionGroup $group): array
    {
        $return   = [];
        $journals = $group->transactionJournals->pluck('id')->toArray();
        $set      = TransactionJournalLink
            ::where(
                static function (Builder $q) use ($journals) {
                    $q->whereIn('source_id', $journals);
                    $q->orWhereIn('destination_id', $journals);
                }
            )
            ->with(['source', 'destination', 'source.transactions'])
            ->leftJoin('link_types', 'link_types.id', '=', 'journal_links.link_type_id')
            ->get(['journal_links.*', 'link_types.inward', 'link_types.outward']);
        /** @var TransactionJournalLink $entry */
        foreach ($set as $entry) {
            $journalId          = in_array($entry->source_id, $journals, true) ? $entry->source_id : $entry->destination_id;
            $return[$journalId] = $return[$journalId] ?? [];

            if ($journalId === $entry->source_id) {
                $amount               = $this->getFormattedAmount($entry->destination);
                $foreignAmount        = $this->getFormattedForeignAmount($entry->destination);
                $return[$journalId][] = [
                    'id'             => $entry->id,
                    'link'           => $entry->outward,
                    'group'          => $entry->destination->transaction_group_id,
                    'description'    => $entry->destination->description,
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
                    'amount'         => $amount,
                    'foreign_amount' => $foreignAmount,
                ];
            }
        }

        return $return;
    }

    /**
     * Return object with all found meta field things as Carbon objects.
     *
     * @param int   $journalId
     * @param array $fields
     *
     * @return NullArrayObject
     * @throws Exception
     */
    public function getMetaDateFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = DB
            ::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data']);
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = new Carbon(json_decode($row->data));
        }

        return new NullArrayObject($return);
    }

    /**
     * Return object with all found meta field things.
     *
     * @param int   $journalId
     * @param array $fields
     *
     * @return NullArrayObject
     */
    public function getMetaFields(int $journalId, array $fields): NullArrayObject
    {
        $query  = DB
            ::table('journal_meta')
            ->where('transaction_journal_id', $journalId)
            ->whereIn('name', $fields)
            ->whereNull('deleted_at')
            ->get(['name', 'data']);
        $return = [];

        foreach ($query as $row) {
            $return[$row->name] = json_decode($row->data);
        }

        return new NullArrayObject($return);
    }

    /**
     * Get the note text for a journal (by ID).
     *
     * @param int $journalId
     *
     * @return string|null
     */
    public function getNoteText(int $journalId): ?string
    {
        /** @var Note $note */
        $note = Note
            ::where('noteable_id', $journalId)
            ->where('noteable_type', TransactionJournal::class)
            ->first();
        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * Return all piggy bank events for all journals in the group.
     *
     * @param TransactionGroup $group
     *
     * @return array
     */
    public function getPiggyEvents(TransactionGroup $group): array
    {
        $return   = [];
        $journals = $group->transactionJournals->pluck('id')->toArray();
        $data     = PiggyBankEvent
            ::whereIn('transaction_journal_id', $journals)
            ->with('piggyBank', 'piggyBank.account')
            ->get(['piggy_bank_events.*']);
        /** @var PiggyBankEvent $row */
        foreach ($data as $row) {
            // get currency preference.
            $currencyPreference = AccountMeta
                ::where('account_id', $row->piggyBank->account_id)
                ->where('name', 'currency_id')
                ->first();
            if (null !== $currencyPreference) {
                $currency = TransactionCurrency::where('id', $currencyPreference->data)->first();
            }
            if (null === $currencyPreference) {
                $currencyCode = app('preferences')->getForUser($this->user, 'currencyPreference', 'EUR')->data;
                $currency     = TransactionCurrency::where('code', $currencyCode)->first();
            }
            $journalId          = (int)$row->transaction_journal_id;
            $return[$journalId] = $return[$journalId] ?? [];

            $return[$journalId][] = [
                'piggy'    => $row->piggyBank->name,
                'piggy_id' => $row->piggy_bank_id,
                'amount'   => app('amount')->formatAnything($currency, $row->amount),
            ];
        }

        return $return;
    }

    /**
     * Get the tags for a journal (by ID).
     *
     * @param int $journalId
     *
     * @return array
     */
    public function getTags(int $journalId): array
    {
        $result = DB
            ::table('tag_transaction_journal')
            ->leftJoin('tags', 'tag_transaction_journal.tag_id', '=', 'tags.id')
            ->where('tag_transaction_journal.transaction_journal_id', $journalId)
            ->get(['tags.tag']);

        return $result->pluck('tag')->toArray();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionGroup
     * @throws DuplicateTransactionException
     */
    public function store(array $data): TransactionGroup
    {
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($this->user);
        try {
            return $factory->create($data);
        } catch (DuplicateTransactionException $e) {
            Log::warning('Group repository caught group factory with a duplicate exception!');
            throw new DuplicateTransactionException($e->getMessage());
        }


    }

    /**
     * @param TransactionGroup $transactionGroup
     * @param array            $data
     *
     * @return TransactionGroup
     *
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        /** @var GroupUpdateService $service */
        $service = app(GroupUpdateService::class);

        return $service->update($transactionGroup, $data);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     */
    private function expandJournal(TransactionJournal $journal): array
    {
        $array                      = $journal->toArray();
        $array['transactions']      = [];
        $array['meta']              = [];
        $array['tags']              = [];
        $array['categories']        = [];
        $array['budgets']           = [];
        $array['notes']             = [];
        $array['locations']         = [];
        $array['attachments']       = [];
        $array['links']             = [];
        $array['piggy_bank_events'] = [];

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $array['transactions'][] = $this->expandTransaction($transaction);
        }
        foreach ($journal->transactionJournalMeta as $meta) {
            $array['meta'][] = $meta->toArray();
        }

        foreach ($journal->tags as $tag) {
            $array['tags'][] = $tag->toArray();
        }
        foreach ($journal->categories as $category) {
            $array['categories'][] = $category->toArray();
        }

        foreach ($journal->budgets as $budget) {
            $array['budgets'][] = $budget->toArray();
        }
        foreach ($journal->notes as $note) {
            $array['notes'][] = $note->toArray();
        }

        foreach ($journal->attachments as $attachment) {
            $array['attachments'][] = $attachment->toArray();
        }
        // TODO apparantly this doesnt work.
        foreach ($journal->sourceJournalLinks as $link) {
            $array['links'][] = $link->toArray();
        }
        foreach ($journal->destJournalLinks as $link) {
            $array['links'][] = $link->toArray();
        }

        foreach ($journal->piggyBankEvents as $event) {
            $array['piggy_bank_events'][] = $event->toArray();
        }

        return $array;
    }

    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    private function expandTransaction(Transaction $transaction): array
    {
        $array = $transaction->toArray();
        $array['account'] = $transaction->account->toArray();
        $array['budgets'] = [];
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
     * @param TransactionJournal $journal
     *
     * @return string
     */
    private function getFormattedAmount(TransactionJournal $journal): string
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions->first();
        $currency    = $transaction->transactionCurrency;
        $type        = $journal->transactionType->type;
        $amount      = app('steam')->positive($transaction->amount);
        $return      = '';
        if (TransactionType::WITHDRAWAL === $type) {
            $return = app('amount')->formatAnything($currency, app('steam')->negative($amount));
        }
        if (TransactionType::WITHDRAWAL !== $type) {
            $return = app('amount')->formatAnything($currency, $amount);

        }

        return $return;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    private function getFormattedForeignAmount(TransactionJournal $journal): string
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions->first();
        if (null === $transaction->foreign_amount) {
            return '';
        }
        $currency = $transaction->foreignCurrency;
        $type     = $journal->transactionType->type;
        $amount   = app('steam')->positive($transaction->foreign_amount);
        $return   = '';
        if (TransactionType::WITHDRAWAL === $type) {
            $return = app('amount')->formatAnything($currency, app('steam')->negative($amount));
        }
        if (TransactionType::WITHDRAWAL !== $type) {
            $return = app('amount')->formatAnything($currency, $amount);
        }

        return $return;
    }
}

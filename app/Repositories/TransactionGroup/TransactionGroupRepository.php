<?php
/**
 * TransactionGroupRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\TransactionGroup;


use Carbon\Carbon;
use DB;
use Exception;
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
use Illuminate\Database\Eloquent\Builder;

/**
 * Class TransactionGroupRepository
 */
class   TransactionGroupRepository implements TransactionGroupRepositoryInterface
{
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
     * @param int $journalId
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
     * @param int $journalId
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
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionGroup
     */
    public function store(array $data): TransactionGroup
    {
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param TransactionGroup $transactionGroup
     * @param array $data
     *
     * @return TransactionGroup
     *
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        /** @var GroupUpdateService $service */
        $service      = app(GroupUpdateService::class);
        return $service->update($transactionGroup, $data);
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

    /**
     * @param TransactionGroup $group
     */
    public function destroy(TransactionGroup $group): void
    {
        /** @var TransactionGroupDestroyService $service */
        $service = new TransactionGroupDestroyService;
        $service->destroy($group);
    }
}
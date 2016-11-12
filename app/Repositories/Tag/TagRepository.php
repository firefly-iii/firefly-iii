<?php
/**
 * TagRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Tag;


use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagRepository
 *
 * @package FireflyIII\Repositories\Tag
 */
class TagRepository implements TagRepositoryInterface
{

    /** @var User */
    private $user;

    /**
     * TagRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     *
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    public function connect(TransactionJournal $journal, Tag $tag): bool
    {
        /*
         * Already connected:
         */
        if ($journal->tags()->find($tag->id)) {
            Log::error(sprintf('Cannot find tag #%d', $tag->id));

            return false;
        }

        switch ($tag->tagMode) {
            case 'nothing':
                Log::debug(sprintf('Tag #%d connected', $tag->id));
                $journal->tags()->save($tag);
                $journal->save();

                return true;
            case 'balancingAct':
                return $this->connectBalancingAct($journal, $tag);
            case 'advancePayment':
                return $this->connectAdvancePayment($journal, $tag);
        }

        return false;
    }

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    public function destroy(Tag $tag): bool
    {
        $tag->delete();

        return true;
    }

    /**
     * @param int $tagId
     *
     * @return Tag
     */
    public function find(int $tagId) : Tag
    {
        $tag = $this->user->tags()->find($tagId);
        if (is_null($tag)) {
            $tag = new Tag;
        }

        return $tag;
    }

    /**
     * @param string $tag
     *
     * @return Tag
     */
    public function findByTag(string $tag) : Tag
    {
        $tags = $this->user->tags()->get();
        /** @var Tag $tag */
        foreach ($tags as $databaseTag) {
            if ($databaseTag->tag === $tag) {
                return $databaseTag;
            }
        }

        return new Tag;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        /** @var Collection $tags */
        $tags = $this->user->tags()->get();
        $tags = $tags->sortBy(
            function (Tag $tag) {
                return strtolower($tag->tag);
            }
        );

        return $tags;
    }

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag
    {
        $tag              = new Tag;
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->tagMode     = $data['tagMode'];
        $tag->user()->associate($this->user);
        $tag->save();

        return $tag;


    }

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->tagMode     = $data['tagMode'];
        $tag->save();

        return $tag;
    }

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    protected function connectAdvancePayment(TransactionJournal $journal, Tag $tag): bool
    {
        $type        = $journal->transactionType->type;
        $withdrawals = $tag->transactionJournals()
                           ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
                           ->where('transaction_types.type', TransactionType::WITHDRAWAL)->count();
        $deposits    = $tag->transactionJournals()
                           ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
                           ->where('transaction_types.type', TransactionType::DEPOSIT)->count();

        if ($type === TransactionType::TRANSFER) { // advance payments cannot accept transfers:
            Log::error(sprintf('Journal #%d is a transfer and cannot connect to tag #%d', $journal->id, $tag->id));

            return false;
        }

        // the first transaction to be attached to this tag is attached just like that:
        if ($withdrawals < 1 && $deposits < 1) {
            Log::debug(sprintf('Tag #%d has 0 withdrawals and 0 deposits so its fine.', $tag->id));
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }

        // if withdrawal and already has a withdrawal, return false:
        if ($type === TransactionType::WITHDRAWAL && $withdrawals > 0) {
            Log::error(sprintf('Journal #%d is a withdrawal but tag already has %d withdrawal(s).', $journal->id, $withdrawals));

            return false;
        }

        // if already has transaction journals, must match ALL asset account id's:
        if ($deposits > 0 || $withdrawals == 1) {
            Log::debug('Need to match all asset accounts.');

            return $this->matchAll($journal, $tag);
        }

        // this statement is unreachable.
        return false;

    }

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    protected function connectBalancingAct(TransactionJournal $journal, Tag $tag): bool
    {
        $type        = $journal->transactionType->type;
        $withdrawals = $tag->transactionJournals()
                           ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
                           ->where('transaction_types.type', TransactionType::WITHDRAWAL)->count();
        $transfers   = $tag->transactionJournals()
                           ->leftJoin('transaction_types', 'transaction_types.id', 'transaction_journals.transaction_type_id')
                           ->where('transaction_types.type', TransactionType::TRANSFER)->count();


        Log::debug(sprintf('Journal #%d is a %s', $journal->id, $type));

        // only if this is the only withdrawal.
        if ($type === TransactionType::WITHDRAWAL && $withdrawals < 1) {
            Log::debug('Will connect this journal because it is the only withdrawal in this tag.');
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }
        // and only if this is the only transfer
        if ($type === TransactionType::TRANSFER && $transfers < 1) {
            Log::debug('Will connect this journal because it is the only transfer in this tag.');
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }
        Log::error(
            sprintf(
                'Tag #%d has %d withdrawals and %d transfers and cannot contain %s #%d',
                $tag->id, $withdrawals, $transfers, $type, $journal->id
            )
        );

        // ignore expense
        return false;

    }

    /**
     * The incoming journal ($journal)'s accounts (source accounts for a withdrawal, destination accounts for a deposit)
     * must match the already existing transaction's accounts exactly.
     *
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     *
     * @return bool
     */
    private function matchAll(TransactionJournal $journal, Tag $tag): bool
    {
        $journalSources      = join(',', TransactionJournal::sourceAccountList($journal)->pluck('id')->toArray());
        $journalDestinations = join(',', TransactionJournal::destinationAccountList($journal)->pluck('id')->toArray());
        $match               = true;
        $journals            = $tag->transactionJournals()->get(['transaction_journals.*']);

        Log::debug(sprintf('Tag #%d has %d journals to verify:', $tag->id, $journals->count()));

        /** @var TransactionJournal $original */
        foreach ($journals as $original) {
            // $checkAccount is the source_account for a withdrawal
            // $checkAccount is the destination_account for a deposit
            $originalSources      = join(',', TransactionJournal::sourceAccountList($original)->pluck('id')->toArray());
            $originalDestinations = join(',', TransactionJournal::destinationAccountList($original)->pluck('id')->toArray());

            if ($original->isWithdrawal() && $originalSources !== $journalDestinations) {
                Log::debug(sprintf('Original journal #%d is a withdrawal.', $original->id));
                Log::debug(sprintf('Journal #%d must have these destination accounts: %s', $journal->id, $originalSources));
                Log::debug(sprintf('Journal #%d actually these destination accounts: %s', $journal->id, $journalDestinations));
                Log::debug('So match is FALSE');

                $match = false;
            }
            if ($original->isDeposit() && $originalDestinations !== $journalSources) {
                Log::debug(sprintf('Original journal #%d is a deposit.', $original->id));
                Log::debug(sprintf('Journal #%d must have these destination accounts: %s', $journal->id, $originalSources));
                Log::debug(sprintf('Journal #%d actually these destination accounts: %s', $journal->id, $journalDestinations));
                Log::debug('So match is FALSE');

                $match = false;
            }

        }
        if ($match) {
            Log::debug(sprintf('Match is true, connect journal #%d with tag #%d.', $journal->id, $tag->id));
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }

        return false;
    }
}

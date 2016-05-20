<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Tag;


use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

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
            return false;
        }

        switch ($tag->tagMode) {
            case 'nothing':
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
        /** @var TransactionType $transfer */
        $transfer = TransactionType::whereType(TransactionType::TRANSFER)->first();
        /** @var TransactionType $withdrawal */
        $withdrawal = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        /** @var TransactionType $deposit */
        $deposit = TransactionType::whereType(TransactionType::DEPOSIT)->first();

        $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
        $deposits    = $tag->transactionjournals()->where('transaction_type_id', $deposit->id)->count();

        if ($journal->transaction_type_id == $transfer->id) { // advance payments cannot accept transfers:
            return false;
        }

        // the first transaction to be attached to this tag is attached just like that:
        if ($withdrawals < 1 && $deposits < 1) {
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }

        // if withdrawal and already has a withdrawal, return false:
        if ($journal->transaction_type_id == $withdrawal->id && $withdrawals == 1) {
            return false;
        }

        // if already has transaction journals, must match ALL asset account id's:
        if ($deposits > 0 || $withdrawals == 1) {
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
        /** @var TransactionType $withdrawal */
        $withdrawal  = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
        /** @var TransactionType $transfer */
        $transfer  = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $transfers = $tag->transactionjournals()->where('transaction_type_id', $transfer->id)->count();


        // only if this is the only withdrawal.
        if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }
        // and only if this is the only transfer
        if ($journal->transaction_type_id == $transfer->id && $transfers < 1) {
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }

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
    protected function matchAll(TransactionJournal $journal, Tag $tag): bool
    {
        $checkSources      = join(',', TransactionJournal::sourceAccountList($journal)->pluck('id')->toArray());
        $checkDestinations = join(',', TransactionJournal::destinationAccountList($journal)->pluck('id')->toArray());

        $match = true;
        /** @var TransactionJournal $check */
        foreach ($tag->transactionjournals as $check) {
            // $checkAccount is the source_account for a withdrawal
            // $checkAccount is the destination_account for a deposit
            $thisSources      = join(',', TransactionJournal::sourceAccountList($check)->pluck('id')->toArray());
            $thisDestinations = join(',', TransactionJournal::destinationAccountList($check)->pluck('id')->toArray());

            if ($check->isWithdrawal() && $thisSources !== $checkSources) {
                $match = false;
            }
            if ($check->isDeposit() && $thisDestinations !== $checkDestinations) {
                $match = false;
            }

        }
        if ($match) {
            $journal->tags()->save($tag);
            $journal->save();

            return true;
        }

        return false;
    }
}

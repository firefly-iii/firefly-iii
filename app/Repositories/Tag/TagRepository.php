<?php

namespace FireflyIII\Repositories\Tag;


use Auth;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Class TagRepository
 *
 * @package FireflyIII\Repositories\Tag
 */
class TagRepository implements TagRepositoryInterface
{

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five.
     *
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return boolean
     */
    public function connect(TransactionJournal $journal, Tag $tag)
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

                return true;
                break;
            case 'balancingAct':
                return $this->connectBalancingAct($journal, $tag);
                break;
            case 'advancePayment':
                return $this->connectAdvancePayment($journal, $tag);
                break;
        }

        return false;
    }

    /**
     * @param Tag $tag
     *
     * @return boolean
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return true;
    }

    /**
     * @return Collection
     */
    public function get()
    {
        /** @var Collection $tags */
        $tags = Auth::user()->tags()->get();
        $tags->sortBy(
            function (Tag $tag) {
                return $tag->tag;
            }
        );

        return $tags;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data)
    {
        $tag              = new Tag;
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->tagMode     = $data['tagMode'];
        $tag->user()->associate(Auth::user());
        $tag->save();

        return $tag;


    }

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data)
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
     * @return boolean
     */
    protected function connectBalancingAct(TransactionJournal $journal, Tag $tag)
    {
        /** @var TransactionType $withdrawal */
        $withdrawal  = TransactionType::whereType('Withdrawal')->first();
        $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
        /** @var TransactionType $transfer */
        $transfer  = TransactionType::whereType('Transfer')->first();
        $transfers = $tag->transactionjournals()->where('transaction_type_id', $transfer->id)->count();


        // only if this is the only withdrawal.
        if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
            $journal->tags()->save($tag);

            return true;
        }
        // and only if this is the only transfer
        if ($journal->transaction_type_id == $transfer->id && $transfers < 1) {
            $journal->tags()->save($tag);

            return true;
        }

        // ignore expense
        return false;

    }

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return boolean
     */
    protected function connectAdvancePayment(TransactionJournal $journal, Tag $tag)
    {
        /** @var TransactionType $transfer */
        $transfer = TransactionType::whereType('Transfer')->first();
        /** @var TransactionType $withdrawal */
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        /** @var TransactionType $deposit */
        $deposit = TransactionType::whereType('Deposit')->first();

        $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
        $deposits    = $tag->transactionjournals()->where('transaction_type_id', $deposit->id)->count();

        // advance payments cannot accept transfers:
        if ($journal->transaction_type_id == $transfer->id) {
            return false;
        }

        // the first transaction to be attached to this
        // tag is attached just like that:
        if ($withdrawals < 1 && $deposits < 1) {
            $journal->tags()->save($tag);

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

        return false;

    }

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    protected function matchAll(TransactionJournal $journal, Tag $tag)
    {
        $match = true;
        /** @var TransactionJournal $check */
        foreach ($tag->transactionjournals as $check) {
            if ($check->assetAccount->id != $journal->assetAccount->id) {
                $match = false;
            }
        }
        if ($match) {
            $journal->tags()->save($tag);

            return true;
        }
        return false;
    }
}

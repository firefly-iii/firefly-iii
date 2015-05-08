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

        if ($tag->tagMode == 'nothing') {
            // save it, no problem:
            $journal->tags()->save($tag);

            return true;
        }

        /*
         * get some withdrawal types:
         */
        /** @var TransactionType $withdrawal */
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        /** @var TransactionType $deposit */
        $deposit = TransactionType::whereType('Deposit')->first();
        /** @var TransactionType $transfer */
        $transfer = TransactionType::whereType('Transfer')->first();

        $withdrawals = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->count();
        $transfers   = $tag->transactionjournals()->where('transaction_type_id', $transfer->id)->count();

        if ($tag->tagMode == 'balancingAct') {

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
        if ($tag->tagMode == 'advancePayment') {


            // only if this is the only withdrawal
            if ($journal->transaction_type_id == $withdrawal->id && $withdrawals < 1) {
                $journal->tags()->save($tag);

                return true;
            }

            // only if this is a deposit.
            if ($journal->transaction_type_id == $deposit->id) {

                // if this is a deposit, account must match the current only journal
                // (if already present):
                $currentWithdrawal = $tag->transactionjournals()->where('transaction_type_id', $withdrawal->id)->first();

                if ($currentWithdrawal && $currentWithdrawal->assetAccount->id == $journal->assetAccount->id) {
                    $journal->tags()->save($tag);

                    return true;
                } else {
                    if (is_null($currentWithdrawal)) {
                        $journal->tags()->save($tag);

                        return true;
                    }
                }
            }

            return false;
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
}

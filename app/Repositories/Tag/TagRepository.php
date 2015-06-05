<?php

namespace FireflyIII\Repositories\Tag;


use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;

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
            case 'balancingAct':
                return $this->connectBalancingAct($journal, $tag);
            case 'advancePayment':
                return $this->connectAdvancePayment($journal, $tag);
        }

        return false;
    }

    /**
     * This method scans the transaction journals from or to the given asset account
     * and checks if these are part of a balancing act. If so, it will sum up the amounts
     * transferred into the balancing act (if any) and return this amount.
     *
     * This method effectively tells you the amount of money that has been balanced out
     * correctly in the given period for the given account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return string
     */
    public function coveredByBalancingActs(Account $account, Carbon $start, Carbon $end)
    {
        // the quickest way to do this is by scanning all balancingAct tags
        // because there will be less of them any way.
        $tags   = Auth::user()->tags()->where('tagMode', 'balancingAct')->get();
        $amount = '0';
        bcscale(2);

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $journals = $tag->transactionjournals()->after($start)->before($end)->transactionTypes(['Transfer'])->get(['transaction_journals.*']);

            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                if ($journal->destination_account->id == $account->id) {
                    $amount = bcadd($amount, $journal->amount);
                }
            }
        }

        return $amount;
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
    // @codeCoverageIgnoreEnd

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
     * Can a tag become an advance payment?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowAdvance(Tag $tag)
    {
        /*
         * If this tag is a balancing act, and it contains transfers, it cannot be
         * changes to an advancePayment.
         */

        if ($tag->tagMode == 'balancingAct' || $tag->tagMode == 'nothing') {
            foreach ($tag->transactionjournals as $journal) {
                if ($journal->transactionType->type == 'Transfer') {
                    return false;
                }
            }
        }

        /*
         * If this tag contains more than one expenses, it cannot become an advance payment.
         */
        $count = 0;
        foreach ($tag->transactionjournals as $journal) {
            if ($journal->transactionType->type == 'Withdrawal') {
                $count++;
            }
        }
        if ($count > 1) {
            return false;
        }

        return true;

    }

    /**
     * Can a tag become a balancing act?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowBalancing(Tag $tag)
    {
        /*
         * If has more than two transactions already, cannot become a balancing act:
         */
        if ($tag->transactionjournals->count() > 2) {
            return false;
        }

        /*
         * If any transaction is a deposit, cannot become a balancing act.
         */
        foreach ($tag->transactionjournals as $journal) {
            if ($journal->transactionType->type == 'Deposit') {
                return false;
            }
        }
        return true;
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

        // this statement is unreachable.
        return false; // @codeCoverageIgnore

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
            if ($check->asset_account->id != $journal->asset_account->id) {
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

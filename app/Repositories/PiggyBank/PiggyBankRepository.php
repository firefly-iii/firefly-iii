<?php

namespace FireflyIII\Repositories\PiggyBank;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class PiggyBankRepository
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{

    /**
     * @param PiggyBank $piggyBank
     * @param           $amount
     *
     * @return bool
     */
    public function createEvent(PiggyBank $piggyBank, $amount)
    {
        PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

        return true;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank)
    {
        return $piggyBank->delete();
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEventSummarySet(PiggyBank $piggyBank)
    {
        return DB::table('piggy_bank_events')->where('piggy_bank_id', $piggyBank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank)
    {
        return $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /**
     * @return Collection
     */
    public function getPiggyBanks()
    {
        /** @var Collection $set */
        $set = Auth::user()->piggyBanks()->orderBy('order', 'ASC')->get();

        return $set;
    }

    /**
     * Set all piggy banks to order 0.
     *
     * @return boolean
     */
    public function reset()
    {
        // split query to make it work in sqlite:
        $set = PiggyBank::
        leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.id')
                        ->where('accounts.user_id', Auth::user()->id)->get(['piggy_banks.*']);
        foreach ($set as $e) {
            $e->order = 0;
            $e->save();
        }

        return true;
    }

    /**
     *
     * set id of piggy bank.
     *
     * @param int $id
     * @param int $order
     *
     * @return void
     */
    public function setOrder($id, $order)
    {
        $piggyBank = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', Auth::user()->id)
                              ->where('piggy_banks.id', $id)->first(['piggy_banks.*']);
        if ($piggyBank) {
            $piggyBank->order = $order;
            $piggyBank->save();
        }
    }

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data)
    {
        $data['remind_me'] = isset($data['remind_me']) && $data['remind_me'] == '1' ? true : false;
        $piggyBank         = PiggyBank::create($data);

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data)
    {
        /**
         * 'rep_length'   => $request->get('rep_length'),
         * 'rep_every'    => intval($request->get('rep_every')),
         * 'rep_times'    => intval($request->get('rep_times')),
         * 'remind_me'    => intval($request->get('remind_me')) == 1 ? true : false ,
         * 'reminder'     => $request->get('reminder'),
         */

        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = intval($data['account_id']);
        $piggyBank->targetamount = floatval($data['targetamount']);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->reminder     = $data['reminder'];
        $piggyBank->startdate    = $data['startdate'];
        $piggyBank->remind_me    = isset($data['remind_me']) && $data['remind_me'] == '1' ? 1 : 0;

        $piggyBank->save();

        return $piggyBank;
    }
}

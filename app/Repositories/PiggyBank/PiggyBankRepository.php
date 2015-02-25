<?php

namespace FireflyIII\Repositories\PiggyBank;

use FireflyIII\Models\PiggyBank;

/**
 * Class PiggyBankRepository
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data)
    {

        $piggyBank = PiggyBank::create(
            [

                'repeats'      => $data['repeats'],
                'name'         => $data['name'],
                'account_id'   => $data['account_id'],
                'targetamount' => $data['targetamount'],
                'startdate'    => $data['startdate'],
                'targetdate'   => $data['targetdate'],
                'reminder'     => $data['reminder'],
            ]
        );

        return $piggyBank;
    }

    /**
     * @param PiggyBank $account
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data)
    {

        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = intval($data['account_id']);
        $piggyBank->targetamount = floatval($data['targetamount']);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->reminder     = $data['reminder'];
        $piggyBank->save();
        return $piggyBank;
    }
}
<?php

namespace FireflyIII\Repositories\PiggyBank;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Support\Collection;

/**
 * Interface PiggyBankRepositoryInterface
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
interface PiggyBankRepositoryInterface
{

    /**
     *
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param PiggyBankRepetition $repetition
     *
     * @return Collection
     */
    public function calculateParts(PiggyBankRepetition $repetition);

    /**
     * @return Collection
     */
    public function getPiggyBanks();

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank);

    /**
     * @param array $data
     *
     * @return PiggyBankPart
     */
    public function createPiggyBankPart(array $data);

    /**
     * @param PiggyBank $piggyBank
     * @param           $amount
     *
     * @return bool
     */
    public function createEvent(PiggyBank $piggyBank, $amount);

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEventSummarySet(PiggyBank $piggyBank);

    /**
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank);

    /**
     * Set all piggy banks to order 0.
     *
     * @return void
     */
    public function reset();

    /**
     *
     * set id of piggy bank.
     *
     * @param int $id
     * @param int $order
     *
     * @return void
     */
    public function setOrder($id, $order);


    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data);

    /**
     * @param PiggyBank $account
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data);
}

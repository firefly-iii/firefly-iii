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
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
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
     * @param array $data
     *
     * @return PiggyBankPart
     */
    public function createPiggyBankPart(array $data);

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

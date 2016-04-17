<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\PiggyBank;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use Illuminate\Support\Collection;

/**
 * Interface PiggyBankRepositoryInterface
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
interface PiggyBankRepositoryInterface
{

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent;

    /**
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank): bool;

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEventSummarySet(PiggyBank $piggyBank) : Collection;

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank) : Collection;

    /**
     * @return int
     */
    public function getMaxOrder(): int;

    /**
     * @return Collection
     */
    public function getPiggyBanks() : Collection;

    /**
     * Set all piggy banks to order 0.
     *
     * @return bool
     */
    public function reset(): bool;

    /**
     *
     * set id of piggy bank.
     *
     * @param int $piggyBankId
     * @param int $order
     *
     * @return bool
     */
    public function setOrder(int $piggyBankId, int $order): bool;


    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank;

    /**
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank;
}

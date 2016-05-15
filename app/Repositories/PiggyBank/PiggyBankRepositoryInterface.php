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
     * Create a new event.
     *
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent;

    /**
     * Destroy piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank): bool;

    /**
     * Get all events.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank) : Collection;

    /**
     * Highest order of all piggy banks.
     *
     * @return int
     */
    public function getMaxOrder(): int;

    /**
     * Return all piggy banks.
     *
     * @return Collection
     */
    public function getPiggyBanks() : Collection;

    /**
     * Also add amount in name.
     *
     * @return Collection
     */
    public function getPiggyBanksWithAmount() : Collection;

    /**
     * Set all piggy banks to order 0.
     *
     * @return bool
     */
    public function reset(): bool;

    /**
     * Set specific piggy bank to specific order.
     *
     * @param int $piggyBankId
     * @param int $order
     *
     * @return bool
     */
    public function setOrder(int $piggyBankId, int $order): bool;


    /**
     * Store new piggy bank.
     *
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank;

    /**
     * Update existing piggy bank.
     *
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank;
}

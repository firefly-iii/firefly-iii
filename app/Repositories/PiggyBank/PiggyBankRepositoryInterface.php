<?php
/**
 * PiggyBankRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
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
     * @return bool
     */
    public function addAmount(PiggyBank $piggyBank, string $amount): bool;

    /**
     * @param PiggyBankRepetition $repetition
     * @param string              $amount
     *
     * @return string
     */
    public function addAmountToRepetition(PiggyBankRepetition $repetition, string $amount): string;

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool;

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function canRemoveAmount(PiggyBank $piggyBank, string $amount): bool;

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
     * @param PiggyBank          $piggyBank
     * @param string             $amount
     * @param TransactionJournal $journal
     *
     * @return PiggyBankEvent
     */
    public function createEventWithJournal(PiggyBank $piggyBank, string $amount, TransactionJournal $journal): PiggyBankEvent;

    /**
     * Destroy piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank): bool;

    /**
     * @param int $piggyBankid
     *
     * @return PiggyBank
     */
    public function find(int $piggyBankid): PiggyBank;

    /**
     * Get all events.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank): Collection;

    /**
     * Used for connecting to a piggy bank.
     *
     * @param PiggyBank           $piggyBank
     * @param PiggyBankRepetition $repetition
     * @param TransactionJournal  $journal
     *
     * @return string
     */
    public function getExactAmount(PiggyBank $piggyBank, PiggyBankRepetition $repetition, TransactionJournal $journal): string;

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
    public function getPiggyBanks(): Collection;

    /**
     * Also add amount in name.
     *
     * @return Collection
     */
    public function getPiggyBanksWithAmount(): Collection;

    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $date
     *
     * @return PiggyBankRepetition
     */
    public function getRepetition(PiggyBank $piggyBank, Carbon $date): PiggyBankRepetition;

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function removeAmount(PiggyBank $piggyBank, string $amount): bool;

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
     * @param User $user
     */
    public function setUser(User $user);

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

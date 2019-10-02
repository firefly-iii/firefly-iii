<?php
/**
 * PiggyBankRepositoryInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
 * Interface PiggyBankRepositoryInterface.
 */
interface PiggyBankRepositoryInterface
{
    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBank
     */
    public function setCurrentAmount(PiggyBank $piggyBank, string $amount): PiggyBank;

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
     * Correct order of piggies in case of issues.
     */
    public function correctOrder(): void;

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
     * Find by name or return NULL.
     *
     * @param string $name
     *
     * @return PiggyBank|null
     */
    public function findByName(string $name): ?PiggyBank;

    /**
     * @param int $piggyBankId
     *
     * @return PiggyBank|null
     */
    public function findNull(int $piggyBankId): ?PiggyBank;

    /**
     * @param int|null       $piggyBankId
     * @param string|null    $piggyBankName
     *
     * @return PiggyBank|null
     */
    public function findPiggyBank(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank;

    /**
     * Get current amount saved in piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getCurrentAmount(PiggyBank $piggyBank): string;

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
     * Return note for piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getNoteText(PiggyBank $piggyBank): string;

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
     *
     * @return PiggyBankRepetition|null
     */
    public function getRepetition(PiggyBank $piggyBank): ?PiggyBankRepetition;

    /**
     * Returns the suggested amount the user should save per month, or "".
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getSuggestedMonthlyAmount(PiggyBank $piggyBank): string;

    /**
     * Get for piggy account what is left to put in piggies.
     *
     * @param PiggyBank $piggyBank
     * @param Carbon    $date
     *
     * @return string
     */
    public function leftOnAccount(PiggyBank $piggyBank, Carbon $date): string;

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function removeAmount(PiggyBank $piggyBank, string $amount): bool;

    /**
     * Set specific piggy bank to specific order.
     *
     * @param PiggyBank $piggyBank
     * @param int       $order
     *
     * @return bool
     */
    public function setOrder(PiggyBank $piggyBank, int $order): bool;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Store new piggy bank.
     *
     * @param array $data
     *
     * @return PiggyBank|null
     */
    public function store(array $data): ?PiggyBank;

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

<?php

/**
 * PiggyBankRepositoryInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface PiggyBankRepositoryInterface.
 */
interface PiggyBankRepositoryInterface
{
    public function addAmount(PiggyBank $piggyBank, Account $account, string $amount, ?TransactionJournal $journal = null): bool;

    public function addAmountToPiggyBank(PiggyBank $piggyBank, string $amount, TransactionJournal $journal): void;

    public function canAddAmount(PiggyBank $piggyBank, Account $account, string $amount): bool;

    public function canRemoveAmount(PiggyBank $piggyBank, Account $account, string $amount): bool;

    /**
     * Destroy piggy bank.
     */
    public function destroy(PiggyBank $piggyBank): bool;

    public function destroyAll(): void;

    public function find(int $piggyBankId): ?PiggyBank;

    /**
     * Find by name or return NULL.
     */
    public function findByName(string $name): ?PiggyBank;

    public function findPiggyBank(?int $piggyBankId, ?string $piggyBankName): ?PiggyBank;

    public function getAttachments(PiggyBank $piggyBank): Collection;

    /**
     * Get current amount saved in piggy bank.
     */
    public function getCurrentAmount(PiggyBank $piggyBank, ?Account $account = null): string;

    /**
     * Get all events.
     */
    public function getEvents(PiggyBank $piggyBank): Collection;
    /**
     * Get current amount saved in piggy bank.
     */

    /**
     * Used for connecting to a piggy bank.
     */
    public function getExactAmount(PiggyBank $piggyBank, TransactionJournal $journal): string;

    /**
     * Return note for piggy bank.
     */
    public function getNoteText(PiggyBank $piggyBank): string;

    /**
     * Return all piggy banks.
     */
    public function getPiggyBanks(): Collection;

    /**
     * Also add amount in name.
     */
    public function getPiggyBanksWithAmount(): Collection;

    public function getRepetition(PiggyBank $piggyBank, bool $overrule = false): ?PiggyBankRepetition;

    /**
     * Returns the suggested amount the user should save per month, or "".
     */
    public function getSuggestedMonthlyAmount(PiggyBank $piggyBank): string;

    /**
     * Get for piggy account what is left to put in piggies.
     */
    public function leftOnAccount(PiggyBank $piggyBank, Account $account, Carbon $date): string;

    public function purgeAll(): void;

    public function removeAmount(PiggyBank $piggyBank, Account $account, string $amount, ?TransactionJournal $journal = null): bool;

    public function removeAmountFromAll(PiggyBank $piggyBank, string $amount): void;

    public function removeObjectGroup(PiggyBank $piggyBank): PiggyBank;

    public function resetOrder(): void;

    /**
     * Search for piggy banks.
     */
    public function searchPiggyBank(string $query, int $limit): Collection;

    public function setCurrentAmount(PiggyBank $piggyBank, string $amount): PiggyBank;

    public function setObjectGroup(PiggyBank $piggyBank, string $objectGroupTitle): PiggyBank;

    /**
     * Set specific piggy bank to specific order.
     */
    public function setOrder(PiggyBank $piggyBank, int $newOrder): bool;

    public function setUser(null|Authenticatable|User $user): void;

    /**
     * Store new piggy bank.
     *
     * @throws FireflyException
     */
    public function store(array $data): PiggyBank;

    /**
     * Update existing piggy bank.
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank;

    public function updateNote(PiggyBank $piggyBank, string $note): void;
}

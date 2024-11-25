<?php

/**
 * TransactionGroupRepositoryInterface.php
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

namespace FireflyIII\Repositories\TransactionGroup;

use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Location;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface TransactionGroupRepositoryInterface
 */
interface TransactionGroupRepositoryInterface
{
    public function countAttachments(int $journalId): int;

    public function destroy(TransactionGroup $group): void;

    /**
     * Return a group and expand all meta data etc.
     */
    public function expandGroup(TransactionGroup $group): array;

    /**
     * Find a transaction group by its ID.
     */
    public function find(int $groupId): ?TransactionGroup;

    /**
     * Return all attachments for all journals in the group.
     */
    public function getAttachments(TransactionGroup $group): array;

    /**
     * Return all journal links for all journals in the group.
     */
    public function getLinks(TransactionGroup $group): array;

    /**
     * Get the location of a journal or NULL.
     */
    public function getLocation(int $journalId): ?Location;

    /**
     * Return object with all found meta field things as Carbon objects.
     */
    public function getMetaDateFields(int $journalId, array $fields): NullArrayObject;

    /**
     * Return object with all found meta field things.
     */
    public function getMetaFields(int $journalId, array $fields): NullArrayObject;

    /**
     * Get the note text for a journal (by ID).
     */
    public function getNoteText(int $journalId): ?string;

    /**
     * Return all piggy bank events for all journals in the group.
     */
    public function getPiggyEvents(TransactionGroup $group): array;

    /**
     * Get the tags for a journal (by ID) as Tag objects.
     */
    public function getTagObjects(int $journalId): Collection;

    /**
     * Get the tags for a journal (by ID).
     */
    public function getTags(int $journalId): array;

    public function setUser(null|Authenticatable|User $user): void;

    /**
     * Create a new transaction group.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function store(array $data): TransactionGroup;

    /**
     * Update an existing transaction group.
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup;
}

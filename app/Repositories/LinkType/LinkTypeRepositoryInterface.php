<?php

/**
 * LinkTypeRepositoryInterface.php
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

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface LinkTypeRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface LinkTypeRepositoryInterface
{
    public function countJournals(LinkType $linkType): int;

    public function destroy(LinkType $linkType, ?LinkType $moveTo = null): bool;

    public function destroyLink(TransactionJournalLink $link): bool;

    public function find(int $linkTypeId): ?LinkType;

    /**
     * Find link type by name.
     */
    public function findByName(?string $name = null): ?LinkType;

    /**
     * Check if link exists between journals.
     */
    public function findLink(TransactionJournal $one, TransactionJournal $two): bool;

    /**
     * See if such a link already exists (and get it).
     */
    public function findSpecificLink(LinkType $linkType, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink;

    public function get(): Collection;

    /**
     * Return array of all journal ID's for this type of link.
     */
    public function getJournalIds(LinkType $linkType): array;

    public function getJournalLinks(?LinkType $linkType = null): Collection;

    /**
     * Return list of existing connections.
     */
    public function getLinks(TransactionJournal $journal): Collection;

    public function store(array $data): LinkType;

    /**
     * Store link between two journals.
     */
    public function storeLink(array $information, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink;

    public function switchLink(TransactionJournalLink $link): bool;

    public function switchLinkById(int $linkId): bool;

    public function update(LinkType $linkType, array $data): LinkType;

    /**
     * Update an existing transaction journal link.
     */
    public function updateLink(TransactionJournalLink $journalLink, array $data): TransactionJournalLink;
}

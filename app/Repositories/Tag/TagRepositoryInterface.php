<?php

/**
 * TagRepositoryInterface.php
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

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface TagRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface TagRepositoryInterface
{
    public function count(): int;

    /**
     * This method destroys a tag.
     */
    public function destroy(Tag $tag): bool;

    public function periodCollection(Tag $tag, Carbon $start, Carbon $end): array;

    /**
     * Destroy all tags.
     */
    public function destroyAll(): void;

    public function expenseInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    public function find(int $tagId): null|Tag;

    public function findByTag(string $tag): null|Tag;

    public function firstUseDate(Tag $tag): null|Carbon;

    /**
     * This method returns all the user's tags.
     */
    public function get(): Collection;

    public function getAttachments(Tag $tag): Collection;

    /**
     * Return location, or NULL.
     */
    public function getLocation(Tag $tag): null|Location;

    public function getTagsInYear(null|int $year): array;

    public function incomeInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    public function lastUseDate(Tag $tag): null|Carbon;

    /**
     * Will return the newest tag (if known) or NULL.
     */
    public function newestTag(): null|Tag;

    /**
     * Will return the newest tag (if known) or NULL.
     */
    public function oldestTag(): null|Tag;

    /**
     * Find one or more tags based on the query.
     */
    public function searchTag(string $query): Collection;

    /**
     * Search the users tags.
     */
    public function searchTags(string $query, int $limit): Collection;

    /**
     * This method stores a tag.
     */
    public function store(array $data): Tag;

    /**
     * Calculates various amounts in tag.
     */
    public function sumsOfTag(Tag $tag, null|Carbon $start, null|Carbon $end): array;

    /**
     * Find one or more tags that start with the string in the query
     */
    public function tagEndsWith(string $query): Collection;

    /**
     * Find one or more tags that start with the string in the query
     */
    public function tagStartsWith(string $query): Collection;

    public function transferredInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    /**
     * Update a tag.
     */
    public function update(Tag $tag, array $data): Tag;
}

<?php
/**
 * TagRepositoryInterface.php
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

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface TagRepositoryInterface.
 */
interface TagRepositoryInterface
{

    /**
     * Return location, or NULL.
     *
     * @param Tag $tag
     *
     * @return Location|null
     */
    public function getLocation(Tag $tag): ?Location;

    /**
     * Destroy all tags.
     */
    public function destroyAll(): void;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * This method destroys a tag.
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function destroy(Tag $tag): bool;

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Tag $tag, Carbon $start, Carbon $end): string;

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function expenseInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    /**
     * @param string $tag
     *
     * @return Tag|null
     */
    public function findByTag(string $tag): ?Tag;

    /**
     * Find one or more tags based on the query.
     * @param string $query
     *
     * @return Collection
     */
    public function searchTag(string $query): Collection;

    /**
     * @param int $tagId
     *
     * @return Tag|null
     */
    public function findNull(int $tagId): ?Tag;

    /**
     * @param Tag $tag
     *
     * @return Carbon
     */
    public function firstUseDate(Tag $tag): ?Carbon;

    /**
     * This method returns all the user's tags.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function incomeInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    /**
     * @param Tag $tag
     *
     * @return Carbon|null
     */
    public function lastUseDate(Tag $tag): ?Carbon;

    /**
     * Will return the newest tag (if known) or NULL.
     *
     * @return Tag|null
     */
    public function newestTag(): ?Tag;

    /**
     * Will return the newest tag (if known) or NULL.
     *
     * @return Tag|null
     */
    public function oldestTag(): ?Tag;

    /**
     * Search the users tags.
     *
     * @param string $query
     *
     * @return Collection
     */
    public function searchTags(string $query): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Tag $tag, Carbon $start, Carbon $end): string;

    /**
     * This method stores a tag.
     *
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag;

    /**
     * Calculates various amounts in tag.
     *
     * @param Tag         $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return array
     */
    public function sumsOfTag(Tag $tag, ?Carbon $start, ?Carbon $end): array;

    /**
     * Generates a tag cloud.
     *
     * @param int|null $year
     *
     * @return array
     */
    public function tagCloud(?int $year): array;

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function transferredInPeriod(Tag $tag, Carbon $start, Carbon $end): array;

    /**
     * Update a tag.
     *
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag;
}

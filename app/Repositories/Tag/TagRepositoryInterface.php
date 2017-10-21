<?php
/**
 * TagRepositoryInterface.php
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

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;


/**
 * Interface TagRepositoryInterface
 *
 * @package FireflyIII\Repositories\Tag
 */
interface TagRepositoryInterface
{

    /**
     * This method will connect a journal with a tag.
     *
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    public function connect(TransactionJournal $journal, Tag $tag): bool;

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
     * @param int $tagId
     *
     * @return Tag
     */
    public function find(int $tagId): Tag;

    /**
     * @param string $tag
     *
     * @return Tag
     */
    public function findByTag(string $tag): Tag;

    /**
     * @param Tag $tag
     *
     * @return Carbon
     */
    public function firstUseDate(Tag $tag): Carbon;

    /**
     * This method returns all the user's tags.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param string $type
     *
     * @return Collection
     */
    public function getByType(string $type): Collection;

    /**
     * @param Tag $tag
     *
     * @return Carbon
     */
    public function lastUseDate(Tag $tag): Carbon;

    /**
     * @return Tag|null
     */
    public function oldestTag(): ?Tag;

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
     * @param Tag         $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return string
     */
    public function sumOfTag(Tag $tag, ?Carbon $start, ?Carbon $end): string;

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
     * Update a tag.
     *
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag;
}

<?php
/**
 * TagRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowAdvance(Tag $tag): bool;

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowBalancing(Tag $tag): bool;

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

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

declare(strict_types = 1);

namespace FireflyIII\Repositories\Tag;

use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
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
     * This method destroys a tag.
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function destroy(Tag $tag): bool;

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
     * This method returns all the user's tags.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * This method stores a tag.
     *
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag;

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

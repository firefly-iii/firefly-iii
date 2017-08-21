<?php
/**
 * LinkTypeRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Models\LinkType;
use Illuminate\Support\Collection;

/**
 * Interface LinkTypeRepositoryInterface
 *
 * @package FireflyIII\Repositories\LinkType
 */
interface LinkTypeRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return LinkType
     */
    public function find(int $id): LinkType;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param LinkType $linkType
     *
     * @return int
     */
    public function countJournals(LinkType $linkType): int;

    /**
     * @param LinkType $linkType
     * @param LinkType $moveTo
     *
     * @return bool
     */
    public function destroy(LinkType $linkType, LinkType $moveTo): bool;

    /**
     * @param array $data
     *
     * @return LinkType
     */
    public function store(array $data): LinkType;

    /**
     * @param LinkType $linkType
     * @param array    $data
     *
     * @return LinkType
     */
    public function update(LinkType $linkType, array $data): LinkType;

}
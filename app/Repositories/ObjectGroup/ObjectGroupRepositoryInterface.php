<?php

/**
 * ObjectGroupRepositoryInterface.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Models\ObjectGroup;
use Illuminate\Support\Collection;

/**
 * Interface ObjectGroupRepositoryInterface
 */
interface ObjectGroupRepositoryInterface
{
    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function search(string $query): Collection;

    /**
     * Delete empty ones.
     */
    public function deleteEmpty(): void;
    /**
     * Delete all.
     */
    public function deleteAll(): void;

    /**
     * @param ObjectGroup $objectGroup
     *
     * @return Collection
     */
    public function getPiggyBanks(ObjectGroup $objectGroup): Collection;

    /**
     * Sort
     */
    public function sort(): void;

    /**
     * @param ObjectGroup $objectGroup
     * @param int         $index
     *
     * @return ObjectGroup
     */
    public function setOrder(ObjectGroup $objectGroup, int $index): ObjectGroup;

    /**
     * @param ObjectGroup $objectGroup
     * @param array       $data
     *
     * @return ObjectGroup
     */
    public function update(ObjectGroup $objectGroup, array $data): ObjectGroup;

    /**
     * @param ObjectGroup $objectGroup
     */
    public function destroy(ObjectGroup $objectGroup): void;

}

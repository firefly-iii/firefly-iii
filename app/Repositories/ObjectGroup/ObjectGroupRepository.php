<?php

/**
 * ObjectGroupRepository.php
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

use DB;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ObjectGroupRepository
 */
class ObjectGroupRepository implements ObjectGroupRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @inheritDoc
     */
    public function get(): Collection
    {
        return $this->user->objectGroups()
                          ->with(['piggyBanks', 'bills'])
                          ->orderBy('order', 'ASC')->orderBy('title', 'ASC')->get();
    }

    /**
     * @param string $query
     * @param int $limit
     *
     * @return Collection
     */
    public function search(string $query, int $limit): Collection
    {
        $dbQuery = $this->user->objectGroups()->orderBy('order', 'ASC')->orderBy('title', 'ASC');
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where('title', 'LIKE', $search);
            }

        }

        return $dbQuery->take($limit)->get(['object_groups.*']);
    }

    /**
     * @inheritDoc
     */
    public function deleteEmpty(): void
    {
        $all = $this->get();
        /** @var ObjectGroup $group */
        foreach ($all as $group) {
            $count = DB::table('object_groupables')->where('object_groupables.object_group_id', $group->id)->count();
            if (0 === $count) {
                $group->delete();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function sort(): void
    {
        $all = $this->get();
        /**
         * @var int         $index
         * @var ObjectGroup $group
         */
        foreach ($all as $index => $group) {
            $group->order = $index + 1;
            $group->save();
        }
    }

    /**
     * @inheritDoc
     */
    public function setOrder(ObjectGroup $objectGroup, int $order): ObjectGroup
    {
        $order              = 0 === $order ? 1 : $order;
        $objectGroup->order = $order;
        $objectGroup->save();

        Log::debug(sprintf('Objectgroup #%d order is now %d', $objectGroup->id, $order));

        return $objectGroup;
    }

    /**
     * @inheritDoc
     */
    public function update(ObjectGroup $objectGroup, array $data): ObjectGroup
    {
        $objectGroup->title = $data['title'];

        if (isset($data['order'])) {
            $order              = 0 === $data['order'] ? 1 : $data['order'];
            $objectGroup->order = $order;
        }

        $objectGroup->save();

        return $objectGroup;
    }

    /**
     * @inheritDoc
     */
    public function destroy(ObjectGroup $objectGroup): void
    {
        $list = $objectGroup->piggyBanks;
        /** @var PiggyBank $piggy */
        foreach($list as $piggy) {
            $piggy->objectGroups()->sync([]);
            $piggy->save();
        }
        $objectGroup->delete();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function getPiggyBanks(ObjectGroup $objectGroup): Collection
    {
        return $objectGroup->piggyBanks;
    }

    /**
     * @inheritDoc
     */
    public function getBills(ObjectGroup $objectGroup): Collection
    {
        return $objectGroup->bills;
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(): void
    {
        $all = $this->get();
        /** @var ObjectGroup $group */
        foreach ($all as $group) {
            $group->piggyBanks()->sync([]);
            $group->bills()->sync([]);
            $group->delete();
        }
    }
}

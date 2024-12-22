<?php

/**
 * CreatesObjectGroups.php
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

/**
 * Trait CreatesObjectGroups
 */
trait CreatesObjectGroups
{
    protected function findObjectGroupById(int $groupId): ?ObjectGroup
    {
        return $this->user->objectGroups()->where('id', $groupId)->first();
    }

    protected function findOrCreateObjectGroup(string $title): ?ObjectGroup
    {
        $title    = substr($title, 0, 255);
        $maxOrder = $this->getObjectGroupMaxOrder();
        if (!$this->hasObjectGroup($title)) {
            return ObjectGroup::create(
                [
                    'user_id'       => $this->user->id,
                    'user_group_id' => $this->user->user_group_id,
                    'title'         => $title,
                    'order'         => $maxOrder + 1,
                ]
            );
        }

        return $this->findObjectGroup($title);
    }

    protected function getObjectGroupMaxOrder(): int
    {
        return (int) $this->user->objectGroups()->max('order');
    }

    protected function hasObjectGroup(string $title): bool
    {
        return 1 === $this->user->objectGroups()->where('title', $title)->count();
    }

    protected function findObjectGroup(string $title): ?ObjectGroup
    {
        return $this->user->objectGroups()->where('title', $title)->first();
    }
}

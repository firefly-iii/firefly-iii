<?php

/**
 * TagFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;

/**
 * Class TagFactory
 */
class TagFactory
{
    private User $user;
    private UserGroup $userGroup;

    public function findOrCreate(string $tag): ?Tag
    {
        $tag    = trim($tag);
        app('log')->debug(sprintf('Now in TagFactory::findOrCreate("%s")', $tag));

        /** @var null|Tag $dbTag */
        $dbTag  = $this->user->tags()->where('tag', $tag)->first();
        if (null !== $dbTag) {
            app('log')->debug(sprintf('Tag exists (#%d), return it.', $dbTag->id));

            return $dbTag;
        }
        $newTag = $this->create(
            [
                'tag'         => $tag,
                'date'        => null,
                'description' => null,
                'latitude'    => null,
                'longitude'   => null,
                'zoom_level'  => null,
            ]
        );
        if (null === $newTag) {
            app('log')->error(sprintf('TagFactory::findOrCreate("%s") but tag is unexpectedly NULL!', $tag));

            return null;
        }
        app('log')->debug(sprintf('Created new tag #%d ("%s")', $newTag->id, $newTag->tag));

        return $newTag;
    }

    public function create(array $data): ?Tag
    {
        $zoomLevel = 0 === (int) $data['zoom_level'] ? null : (int) $data['zoom_level'];
        $latitude  = 0.0 === (float) $data['latitude'] ? null : (float) $data['latitude'];   // intentional float
        $longitude = 0.0 === (float) $data['longitude'] ? null : (float) $data['longitude']; // intentional float
        $array     = [
            'user_id'       => $this->user->id,
            'user_group_id' => $this->user->user_group_id,
            'tag'           => trim($data['tag']),
            'tagMode'       => 'nothing',
            'date'          => $data['date'],
            'description'   => $data['description'],
            'latitude'      => null,
            'longitude'     => null,
            'zoomLevel'     => null,
        ];

        /** @var null|Tag $tag */
        $tag       = Tag::create($array);
        if (null !== $tag && null !== $latitude && null !== $longitude) {
            // create location object.
            $location             = new Location();
            $location->latitude   = $latitude;
            $location->longitude  = $longitude;
            $location->zoom_level = $zoomLevel;
            $location->locatable()->associate($tag);
            $location->save();
        }

        return $tag;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->userGroup = $user->userGroup;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }
}

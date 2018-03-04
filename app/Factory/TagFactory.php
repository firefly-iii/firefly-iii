<?php
/**
 * TagFactory.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class TagFactory
 */
class TagFactory
{
    /** @var Collection */
    private $tags;
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Tag|null
     */
    public function create(array $data): ?Tag
    {
        return Tag::create(
            [
                'user_id'     => $this->user->id,
                'tag'         => $data['tag'],
                'tagMode'     => 'nothing',
                'date'        => $data['date'],
                'description' => $data['description'],
                'latitude'    => $data['latitude'],
                'longitude '  => $data['longitude'],
                'zoomLevel'   => $data['zoom_level'],
            ]
        );
    }

    /**
     * @param string $tag
     *
     * @return Tag|null
     */
    public function findOrCreate(string $tag): ?Tag
    {
        if (is_null($this->tags)) {
            $this->tags = $this->user->tags()->get();
        }

        /** @var Tag $object */
        foreach ($this->tags as $object) {
            if ($object->tag === $tag) {
                return $object;
            }
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
        $this->tags->push($newTag);

        return $newTag;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
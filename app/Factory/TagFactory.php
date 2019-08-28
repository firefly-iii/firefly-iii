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
/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

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
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param array $data
     *
     * @return Tag|null
     */
    public function create(array $data): ?Tag
    {
        $zoomLevel = 0 === (int)$data['zoom_level'] ? null : (int)$data['zoom_level'];
        $latitude  = 0.0 === (float)$data['latitude'] ? null : (float)$data['latitude'];
        $longitude = 0.0 === (float)$data['longitude'] ? null : (float)$data['longitude'];
        $array     = [
            'user_id'     => $this->user->id,
            'tag'         => trim($data['tag']),
            'tagMode'     => 'nothing',
            'date'        => $data['date'],
            'description' => $data['description'],
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
        ];
        return Tag::create($array);
    }

    /**
     * @param string $tag
     *
     * @return Tag|null
     */
    public function findOrCreate(string $tag): ?Tag
    {
        $tag = trim($tag);

        /** @var Tag $dbTag */
        $dbTag = $this->user->tags()->where('tag', $tag)->first();
        if (null !== $dbTag) {
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

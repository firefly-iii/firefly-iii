<?php

/**
 * TagTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;

/**
 * Class TagTransformer
 */
class TagTransformer extends AbstractTransformer
{
    /**
     * Transform a tag.
     *
     * TODO add spent, earned, transferred, etc.
     */
    public function transform(Tag $tag): array
    {
        $date      = $tag->date?->format('Y-m-d');

        /** @var null|Location $location */
        $location  = $tag->locations()->first();
        $latitude  = null;
        $longitude = null;
        $zoomLevel = null;
        if (null !== $location) {
            $latitude  = $location->latitude;
            $longitude = $location->longitude;
            $zoomLevel = (int)$location->zoom_level;
        }

        return [
            'id'          => $tag->id,
            'created_at'  => $tag->created_at->toAtomString(),
            'updated_at'  => $tag->updated_at->toAtomString(),
            'tag'         => $tag->tag,
            'date'        => $date,
            'description' => '' === $tag->description ? null : $tag->description,
            'longitude'   => $longitude,
            'latitude'    => $latitude,
            'zoom_level'  => $zoomLevel,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/tags/'.$tag->id,
                ],
            ],
        ];
    }
}

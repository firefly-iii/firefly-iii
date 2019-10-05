<?php
/**
 * TagTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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


use FireflyIII\Models\Tag;
use Log;

/**
 * Class TagTransformer
 */
class TagTransformer extends AbstractTransformer
{
    /**
     * TagTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Transform a tag.
     *
     * TODO add spent, earned, transferred, etc.
     *
     * @param Tag $tag
     *
     * @return array
     */
    public function transform(Tag $tag): array
    {
        $date = null === $tag->date ? null : $tag->date->format('Y-m-d');
        $data = [
            'id'          => (int)$tag->id,
            'created_at'  => $tag->created_at->toAtomString(),
            'updated_at'  => $tag->updated_at->toAtomString(),
            'tag'         => $tag->tag,
            'date'        => $date,
            'description' => '' === $tag->description ? null : $tag->description,
            'latitude'    => null === $tag->latitude ? null : (float)$tag->latitude,
            'longitude'   => null === $tag->longitude ? null : (float)$tag->longitude,
            'zoom_level'  => null === $tag->zoomLevel ? null : (int)$tag->zoomLevel,
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => '/tags/' . $tag->id,
                ],
            ],
        ];

        return $data;
    }

}

<?php
/**
 * TagsSpace.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class TagsSpace
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class TagsSpace extends BasicConverter implements ConverterInterface
{

    /**
     * @return Collection
     */
    public function convert(): Collection
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);

        $tags = new Collection;

        $strings = explode(' ', $this->value);
        foreach ($strings as $string) {
            $data = [
                'tag'         => $string,
                'date'        => null,
                'description' => null,
                'latitude'    => null,
                'longitude'   => null,
                'zoomLevel'   => null,
                'tagMode'     => 'nothing',
            ];
            $tag  = $repository->store($data); // should validate first?
            $tags->push($tag);
        }
        $tags = $tags->merge($this->data['tags']);

        return $tags;
    }
}

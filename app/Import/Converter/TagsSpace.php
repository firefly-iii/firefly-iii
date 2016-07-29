<?php
/**
 * TagsSpace.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagsSpace
 *
 * @package FireflyIII\Import\Converter
 */
class TagsSpace extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Collection
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using TagsSpace', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);
            return new Collection;
        }
        $parts = array_unique(explode(' ', $value));
        $set   = new Collection;
        Log::debug('Exploded parts.', $parts);

        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class, [$this->user]);


        /** @var string $part */
        foreach ($parts as $part) {
            if (isset($this->mapping[$part])) {
                Log::debug('Found tag in mapping. Should exist.', ['value' => $part, 'map' => $this->mapping[$part]]);
                $tag = $repository->find(intval($this->mapping[$part]));
                if (!is_null($tag->id)) {
                    Log::debug('Found tag by ID', ['id' => $tag->id]);

                    $set->push($tag);
                    continue;
                }
            }
            // not mapped? Still try to find it first:
            $tag = $repository->findByTag($part);
            if (!is_null($tag->id)) {
                Log::debug('Found tag by name ', ['id' => $tag->id]);

                $set->push($tag);
                continue;
            }
            // create new tag
            $tag = $repository->store(
                [
                    'tag'         => $part,
                    'date'        => null,
                    'description' => $part,
                    'latitude'    => null,
                    'longitude'   => null,
                    'zoomLevel'   => null,
                    'tagMode'     => 'nothing',
                ]
            );
            Log::debug('Created new tag', ['name' => $part, 'id' => $tag->id]);
            $set->push($tag);
        }
        $this->setCertainty(100);

        return $set;

    }
}
<?php
/**
 * TagSplit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagSplit
 *
 * @package FireflyIII\Import\Converter
 */
class TagSplit
{

    /**
     * @param User  $user
     * @param array $mapping
     * @param array $parts
     *
     * @return Collection
     */
    public static function createSetFromSplits(User $user, array $mapping, array $parts): Collection
    {
        $set = new Collection;
        Log::debug('Exploded parts.', $parts);

        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($user);


        /** @var string $part */
        foreach ($parts as $part) {
            if (isset($mapping[$part])) {
                Log::debug('Found tag in mapping. Should exist.', ['value' => $part, 'map' => $mapping[$part]]);
                $tag = $repository->find(intval($mapping[$part]));
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
            }
            if (is_null($tag->id)) {
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
        }

        return $set;
    }

}

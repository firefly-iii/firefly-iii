<?php
/**
 * Tags.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;

/**
 * Class Tags
 *
 * @package FireflyIII\Import\Mapper
 */
class Tags implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $result     = $repository->get();
        $list       = [];

        /** @var Tag $tag */
        foreach ($result as $tag) {
            $list[$tag->id] = $tag->tag;
        }
        asort($list);

        $list = [0 => trans('csv.do_not_map')] + $list;

        return $list;

    }
}

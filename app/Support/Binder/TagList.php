<?php
/**
 * TagList.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TagList
 *
 * @package FireflyIII\Support\Binder
 */
class TagList implements BinderInterface
{

    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route): Collection
    {
        if (auth()->check()) {
            $tags = explode(',', $value);
            /** @var TagRepositoryInterface $repository */
            $repository = app(TagRepositoryInterface::class);
            $allTags    = $repository->get();
            $set        = $allTags->filter(
                function (Tag $tag) use ($tags) {
                    return in_array($tag->tag, $tags);
                }
            );

            if ($set->count() > 0) {
                return $set;
            }
        }
        throw new NotFoundHttpException;
    }
}

<?php
/**
 * TagList.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TagList.
 */
class TagList implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return Collection
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        if (auth()->check()) {

            if ('allTags' === $value) {
                return auth()->user()->tags()
                             ->orderBy('tag', 'ASC')
                             ->get();
            }


            $list = array_unique(array_map('\strtolower', explode(',', $value)));
            Log::debug('List of tags is', $list);
            // @codeCoverageIgnoreStart
            if (0 === count($list)) {
                Log::error('Tag list is empty.');
                throw new NotFoundHttpException;
            }
            // @codeCoverageIgnoreEnd

            /** @var TagRepositoryInterface $repository */
            $repository = app(TagRepositoryInterface::class);
            $repository->setUser(auth()->user());
            $allTags = $repository->get();

            $collection = $allTags->filter(
                static function (Tag $tag) use ($list) {
                    if (in_array(strtolower($tag->tag), $list, true)) {
                        return true;
                    }
                    if (in_array((string)$tag->id, $list, true)) {
                        return true;
                    }

                    return false;
                }
            );

            if ($collection->count() > 0) {
                return $collection;
            }
        }
        Log::error('TagList: user is not logged in.');
        throw new NotFoundHttpException;
    }
}

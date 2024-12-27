<?php

/**
 * TagList.php
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

namespace FireflyIII\Support\Binder;

use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TagList.
 */
class TagList implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Collection
    {
        if (auth()->check()) {
            if ('allTags' === $value) {
                return auth()->user()->tags()
                    ->orderBy('tag', 'ASC')
                    ->get()
                ;
            }
            $list       = array_unique(array_map('\strtolower', explode(',', $value)));
            app('log')->debug('List of tags is', $list);

            if (0 === count($list)) { // @phpstan-ignore-line
                app('log')->error('Tag list is empty.');

                throw new NotFoundHttpException();
            }

            /** @var TagRepositoryInterface $repository */
            $repository = app(TagRepositoryInterface::class);
            $repository->setUser(auth()->user());
            $allTags    = $repository->get();

            $collection = $allTags->filter(
                static function (Tag $tag) use ($list) {
                    if (in_array(strtolower($tag->tag), $list, true)) {
                        Log::debug(sprintf('TagList: (string) found tag #%d ("%s") in list.', $tag->id, $tag->tag));

                        return true;
                    }
                    if (in_array((string) $tag->id, $list, true)) {
                        Log::debug(sprintf('TagList: (id) found tag #%d ("%s") in list.', $tag->id, $tag->tag));

                        return true;
                    }

                    return false;
                }
            );

            if ($collection->count() > 0) {
                return $collection;
            }
        }
        app('log')->error('TagList: user is not logged in.');

        throw new NotFoundHttpException();
    }
}

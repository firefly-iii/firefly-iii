<?php

/**
 * TagOrId.php
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TagOrId.
 */
class TagOrId implements BinderInterface
{
    public static function routeBinder(string $value, Route $route): Tag
    {
        if (auth()->check()) {
            /** @var TagRepositoryInterface $repository */
            $repository = app(TagRepositoryInterface::class);
            $repository->setUser(auth()->user());

            $result = $repository->findByTag($value);
            if (null === $result) {
                $result = $repository->find((int) $value);
            }
            if (null !== $result) {
                return $result;
            }
            app('log')->error('TagOrId: tag not found.');

            throw new NotFoundHttpException();
        }
        app('log')->error('TagOrId: user is not logged in.');

        throw new NotFoundHttpException();
    }
}

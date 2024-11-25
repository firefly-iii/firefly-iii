<?php

/**
 * CLIToken.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CLIToken
 */
class CLIToken implements BinderInterface
{
    /**
     * @return mixed
     *
     * @throws FireflyException
     */
    public static function routeBinder(string $value, Route $route)
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $users      = $repository->all();

        // check for static token
        if ($value === config('firefly.static_cron_token') && 32 === strlen(config('firefly.static_cron_token'))) {
            return $value;
        }

        foreach ($users as $user) {
            $accessToken = app('preferences')->getForUser($user, 'access_token');
            if (null !== $accessToken && $accessToken->data === $value) {
                app('log')->info(sprintf('Recognized user #%d (%s) from his access token.', $user->id, $user->email));

                return $value;
            }
        }
        app('log')->error(sprintf('Recognized no users by access token "%s"', $value));

        throw new NotFoundHttpException();
    }
}

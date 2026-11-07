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

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

/**
 * Class CLIToken
 */
class CLIToken implements BinderInterface
{
    public static function findUserByToken(#[SensitiveParameter] string $token): ?User
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $users      = $repository->all();

        foreach ($users as $user) {
            $accessToken = Preferences::getForUser($user, 'access_token');
            if (null !== $accessToken && hash_equals((string) $accessToken->data, $token)) {
                Log::info(sprintf('Recognized user #%d (%s) from his access token.', $user->id, $user->email));

                return $user;
            }
        }
        Log::error(sprintf('Recognized no users by access token "%s..."', substr($token, 0, 8)));

        return null;
    }

    public static function routeBinder(#[SensitiveParameter] string $value, Route $route): string
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $users      = $repository->all();

        // check for static token
        if (hash_equals($value, (string) config('firefly.static_cron_token')) && 32 === strlen(config('firefly.static_cron_token'))) {
            return $value;
        }

        foreach ($users as $user) {
            $userAccessToken = Preferences::getForUser($user, 'access_token');
            if (null !== $userAccessToken && hash_equals((string) $userAccessToken->data, $value)) {
                Log::info(sprintf('Recognized user #%d (%s) from his access token.', $user->id, $user->email));

                return $value;
            }
        }
        Log::error(sprintf('Recognized no users by access token "%s..."', substr($value, 0, 8)));

        throw new AuthenticationException();
    }
}

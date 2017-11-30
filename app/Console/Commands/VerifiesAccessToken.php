<?php
/**
 * VerifiesAccessToken.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Preferences;

/**
 * Trait VerifiesAccessToken.
 *
 * Verifies user access token for sensitive commands.
 */
trait VerifiesAccessToken
{
    /**
     * Abstract method to make sure trait knows about method "option".
     *
     * @param null $key
     *
     * @return mixed
     */
    abstract public function option($key = null);

    /**
     * Returns false when given token does not match given user token.
     *
     * @return bool
     */
    protected function verifyAccessToken(): bool
    {
        $userId = intval($this->option('user'));
        $token  = strval($this->option('token'));
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find($userId);

        if (null === $user->id) {
            Log::error(sprintf('verifyAccessToken(): no such user for input "%d"', $userId));

            return false;
        }
        $accessToken = Preferences::getForUser($user, 'access_token', null);
        if (null === $accessToken) {
            Log::error(sprintf('User #%d has no access token, so cannot access command line options.', $userId));

            return false;
        }
        if (!($accessToken->data === $token)) {
            Log::error(sprintf('Invalid access token for user #%d.', $userId));

            return false;
        }

        return true;
    }
}

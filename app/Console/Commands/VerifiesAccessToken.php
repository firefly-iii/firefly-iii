<?php
/**
 * VerifiesAccessToken.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Preferences;

/**
 * Trait VerifiesAccessToken
 *
 * Verifies user access token for sensitive commands.
 *
 * @package FireflyIII\Console\Commands
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

        if (is_null($user->id)) {
            Log::error(sprintf('verifyAccessToken(): no such user for input "%d"', $userId));

            return false;
        }
        $accessToken = Preferences::getForUser($user, 'access_token', null);
        if (is_null($accessToken)) {
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
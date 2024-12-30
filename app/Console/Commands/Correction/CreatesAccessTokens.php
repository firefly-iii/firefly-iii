<?php

/**
 * CreateAccessTokens.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

class CreatesAccessTokens extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Creates user access tokens which are used for command line access to personal data.';

    protected $signature   = 'correction:access-tokens';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle(): int
    {
        // make repository:
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $count      = 0;
        $users      = $repository->all();

        /** @var User $user */
        foreach ($users as $user) {
            $pref = app('preferences')->getForUser($user, 'access_token');
            if (null === $pref) {
                $token = $user->generateAccessToken();
                app('preferences')->setForUser($user, 'access_token', $token);
                $this->friendlyInfo(sprintf('Generated access token for user %s', $user->email));
                ++$count;
            }
        }

        return 0;
    }
}

<?php
declare(strict_types=1);
/**
 * CreateAccessTokens.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Correction;

use Exception;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

/**
 * Class CreateAccessTokens
 */
class CreateAccessTokens extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates user access tokens which are used for command line access to personal data.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-access-tokens';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        // make repository:
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $start = microtime(true);
        $count = 0;
        $users = $repository->all();
        /** @var User $user */
        foreach ($users as $user) {
            $pref = app('preferences')->getForUser($user, 'access_token', null);
            if (null === $pref) {
                $token = $user->generateAccessToken();
                app('preferences')->setForUser($user, 'access_token', $token);
                $this->line(sprintf('Generated access token for user %s', $user->email));
                ++$count;
            }
        }
        if (0 === $count) {
            $this->info('All access tokens OK!');
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verify access tokens in %s seconds.', $end));

        return 0;
    }
}

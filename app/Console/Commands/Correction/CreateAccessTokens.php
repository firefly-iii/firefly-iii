<?php
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
    protected $description = 'Creates user access tokens.';
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
        $count = 0;
        $users = User::get();
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

        return 0;
    }
}

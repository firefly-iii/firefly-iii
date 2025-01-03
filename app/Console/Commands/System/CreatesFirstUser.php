<?php

/*
 * CreateFirstUser.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatesFirstUser extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Creates a new user and gives admin rights. Outputs the password on the command line. Strictly for testing.';

    protected $signature   = 'system:create-first-user {email}';
    private UserRepositoryInterface $repository;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ('testing' !== config('app.env')) {
            $this->friendlyError('This command only works in the testing environment.');

            return 1;
        }
        $this->stupidLaravel();
        $count          = $this->repository->count();
        if ($count > 0) {
            $this->friendlyError('Already have more than zero users in DB.');

            return 1;
        }
        $data           = [
            'blocked'      => false,
            'blocked_code' => null,
            'email'        => $this->argument('email'),
            'role'         => 'owner',
        ];
        $password       = \Str::random(24);
        $user           = $this->repository->store($data);
        $user->password = Hash::make($password);
        $user->save();
        $user->setRememberToken(\Str::random(60));

        $this->friendlyInfo(sprintf('Created new admin user (ID #%d) with email address "%s" and password "%s".', $user->id, $user->email, $password));
        $this->friendlyWarning('Change this password.');

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->repository = app(UserRepositoryInterface::class);
    }
}

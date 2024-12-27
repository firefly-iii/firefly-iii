<?php

/**
 * FixAccountOrder.php
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

class CorrectsAccountOrder extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Make sure account order is correct.';
    protected $signature   = 'firefly-iii:fix-account-order';

    private AccountRepositoryInterface $repository;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->stupidLaravel();

        $users = User::get();
        foreach ($users as $user) {
            $this->repository->setUser($user);
            $this->repository->resetAccountOrder();
        }

        $this->friendlyPositive('All accounts are ordered correctly');

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->repository = app(AccountRepositoryInterface::class);
    }
}

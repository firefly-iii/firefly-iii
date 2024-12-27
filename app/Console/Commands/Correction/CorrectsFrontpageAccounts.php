<?php

/*
 * FixFrontpageAccounts.php
 * Copyright (c) 2021 james@firefly-iii.org
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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

class CorrectsFrontpageAccounts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Fixes a preference that may include deleted accounts or accounts of another type.';
    protected $signature   = 'correction:frontpage-accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $preference = app('preferences')->getForUser($user, 'frontpageAccounts');
            if (null !== $preference) {
                $this->fixPreference($preference);
            }
        }
        return 0;
    }

    private function fixPreference(Preference $preference): void
    {
        $fixed      = [];

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        if (null === $preference->user) {
            return;
        }
        $repository->setUser($preference->user);
        $data       = $preference->data;
        if (is_array($data)) {
            /** @var string $accountId */
            foreach ($data as $accountId) {
                $accountIdInt = (int) $accountId;
                $account      = $repository->find($accountIdInt);
                if (null !== $account
                    && in_array($account->accountType->type, [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE], true)
                    && true === $account->active) {
                    $fixed[] = $account->id;
                }
            }
        }
        app('preferences')->setForUser($preference->user, 'frontpageAccounts', $fixed);
    }
}

<?php

/*
 * UpgradeCurrencyPreferences.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Class UpgradeCurrencyPreferences
 */
class UpgradeCurrencyPreferences extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '610_upgrade_currency_prefs';

    protected $description          = 'Upgrade user currency preferences';

    protected $signature            = 'firefly-iii:upgrade-currency-preferences {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->runUpgrade();

        $this->friendlyPositive('Currency preferences migrated.');

        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    private function runUpgrade(): void
    {
        $groups = UserGroup::get();

        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $this->upgradeGroupPreferences($group);
        }

        $users  = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $this->upgradeUserPreferences($user);
        }
    }

    private function upgradeGroupPreferences(UserGroup $group): void
    {
        $currencies = TransactionCurrency::get();
        $enabled    = new Collection();

        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            if ($currency->enabled) {
                $enabled->push($currency);
            }
        }
        $group->currencies()->sync($enabled->pluck('id')->toArray());
    }

    private function upgradeUserPreferences(User $user): void
    {
        $currencies      = TransactionCurrency::get();
        $enabled         = new Collection();

        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            if ($currency->enabled) {
                $enabled->push($currency);
            }
        }
        $user->currencies()->sync($enabled->pluck('id')->toArray());

        // set the default currency for the user and for the group:
        $preference      = $this->getPreference($user);
        $defaultCurrency = TransactionCurrency::where('code', $preference)->first();
        if (null === $defaultCurrency) {
            // get EUR
            $defaultCurrency = TransactionCurrency::where('code', 'EUR')->first();
        }
        $user->currencies()->updateExistingPivot($defaultCurrency->id, ['user_default' => true]);
        $user->userGroup->currencies()->updateExistingPivot($defaultCurrency->id, ['group_default' => true]);
    }

    private function getPreference(User $user): string
    {
        $preference = Preference::where('user_id', $user->id)->where('name', 'currencyPreference')->first(['id', 'user_id', 'name', 'data', 'updated_at', 'created_at']);

        if (null === $preference) {
            return 'EUR';
        }

        if (null !== $preference->data && !is_array($preference->data)) {
            return (string) $preference->data;
        }

        return 'EUR';
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

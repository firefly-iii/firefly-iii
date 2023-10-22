<?php

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
 * TODO DONT FORGET TO ADD THIS TO THE DOCKER BUILD
 */
class UpgradeCurrencyPreferences extends Command
{
    use ShowsFriendlyMessages;

    public const CONFIG_NAME = '610_upgrade_currency_prefs';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade user currency preferences';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:upgrade-currency-preferences {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->runUpgrade();

        $this->friendlyPositive('Currency preferences migrated.');

        //$this->markAsExecuted();

        return 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return string
     */
    private function getPreference(User $user): string
    {
        $preference = Preference::where('user_id', $user->id)->where('name', 'currencyPreference')->first(['id', 'user_id', 'name', 'data', 'updated_at', 'created_at']);

        if (null !== $preference) {
            return (string)$preference->data;
        }
        return 'EUR';
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    private function runUpgrade(): void
    {
        $groups = UserGroup::get();
        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $this->upgradeGroupPreferences($group);
        }

        $users = User::get();
        /** @var User $user */
        foreach ($users as $user) {
            $this->upgradeUserPreferences($user);
        }
    }

    /**
     * @param User $user
     *
     * @return void
     */
    private function upgradeUserPreferences(User $user): void
    {
        $currencies = TransactionCurrency::get();
        $enabled    = new Collection();
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
        if (null === $currency) {
            // get EUR
            $defaultCurrency = TransactionCurrency::where('code', 'EUR')->first();
        }
        $user->currencies()->updateExistingPivot($defaultCurrency->id, ['user_default' => true]);
        $user->userGroup->currencies()->updateExistingPivot($defaultCurrency->id, ['group_default' => true]);
    }

    /**
     * @param UserGroup $group
     *
     * @return void
     */
    private function upgradeGroupPreferences(UserGroup $group)
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
}

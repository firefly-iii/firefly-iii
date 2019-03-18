<?php
/**
 * AccountCurrencies.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Console\Command;
use Log;
use UnexpectedValueException;

/**
 * Class AccountCurrencies
 */
class AccountCurrencies extends Command
{
    public const CONFIG_NAME = '4780_account_currencies';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give all accounts proper currency info.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:account-currencies {--F|force : Force the execution of this command.}';

    /**
     * Each (asset) account must have a reference to a preferred currency. If the account does not have one, it's forced upon the account.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        Log::debug('Now in updateAccountCurrencies()');

        $defaultConfig = (string)config('firefly.default_currency', 'EUR');
        Log::debug(sprintf('System default currency is "%s"', $defaultConfig));

        $accounts = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->get(['accounts.*']);
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts->each(
            function (Account $account) use ($repository, $defaultConfig) {
                $repository->setUser($account->user);
                // get users preference, fall back to system pref.

                // expand and debug routine.
                $defaultCurrencyCode = app('preferences')->getForUser($account->user, 'currencyPreference', $defaultConfig)->data;
                Log::debug(sprintf('Default currency code is "%s"', var_export($defaultCurrencyCode, true)));
                if (!is_string($defaultCurrencyCode)) {
                    $defaultCurrencyCode = $defaultConfig;
                    Log::debug(sprintf('Default currency code is not a string, now set to "%s"', $defaultCurrencyCode));
                }
                $defaultCurrency = TransactionCurrency::where('code', $defaultCurrencyCode)->first();
                $accountCurrency = (int)$repository->getMetaValue($account, 'currency_id');
                $openingBalance  = $account->getOpeningBalance();
                $obCurrency      = (int)$openingBalance->transaction_currency_id;

                if (null === $defaultCurrency) {
                    throw new UnexpectedValueException(sprintf('User has a preference for "%s", but this currency does not exist.', $defaultCurrencyCode));
                }
                Log::debug(
                    sprintf('Found default currency #%d (%s) while searching for "%s"', $defaultCurrency->id, $defaultCurrency->code, $defaultCurrencyCode)
                );

                // both 0? set to default currency:
                if (0 === $accountCurrency && 0 === $obCurrency) {
                    AccountMeta::where('account_id', $account->id)->where('name', 'currency_id')->forceDelete();
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $defaultCurrency->id]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // account is set to 0, opening balance is not?
                if (0 === $accountCurrency && $obCurrency > 0) {
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $obCurrency]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // do not match and opening balance id is not null.
                if ($accountCurrency !== $obCurrency && $openingBalance->id > 0) {
                    // update opening balance:
                    $openingBalance->transaction_currency_id = $accountCurrency;
                    $openingBalance->save();
                    $this->line(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));

                    return true;
                }

                return true;
            }
        );

        $this->markAsExecuted();

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

        return false; // @codeCoverageIgnore
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

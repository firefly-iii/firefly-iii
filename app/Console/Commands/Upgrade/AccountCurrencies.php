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
use FireflyIII\User;
use Illuminate\Console\Command;
use Log;

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

    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * Each (asset) account must have a reference to a preferred currency. If the account does not have one, it's forced upon the account.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->repository = app(AccountRepositoryInterface::class);
        $start            = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        Log::debug('Now in updateAccountCurrencies()');
        $this->updateAccountCurrencies();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed account currencies in %s seconds.', $end));
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

    /**
     * @param Account             $account
     * @param TransactionCurrency $currency
     */
    private function updateAccount(Account $account, TransactionCurrency $currency): void
    {
        $this->repository->setUser($account->user);

        $accountCurrency = (int)$this->repository->getMetaValue($account, 'currency_id');
        $openingBalance  = $account->getOpeningBalance();
        $obCurrency      = (int)$openingBalance->transaction_currency_id;


        // both 0? set to default currency:
        if (0 === $accountCurrency && 0 === $obCurrency) {
            AccountMeta::where('account_id', $account->id)->where('name', 'currency_id')->forceDelete();
            AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $currency->id]);
            $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $currency->code));

            return;
        }

        // account is set to 0, opening balance is not?
        if (0 === $accountCurrency && $obCurrency > 0) {
            AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $obCurrency]);
            $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $currency->code));

            return;
        }

        // do not match and opening balance id is not null.
        if ($accountCurrency !== $obCurrency && $openingBalance->id > 0) {
            // update opening balance:
            $openingBalance->transaction_currency_id = $accountCurrency;
            $openingBalance->save();
            $this->line(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));
        }
    }

    /**
     *
     */
    private function updateAccountCurrencies(): void
    {

        $defaultCurrencyCode = (string)config('firefly.default_currency', 'EUR');
        $users               = User::get();
        foreach ($users as $user) {
            $this->updateCurrenciesForUser($user, $defaultCurrencyCode);
        }
    }

    /**
     * @param User   $user
     * @param string $systemCurrencyCode
     */
    private function updateCurrenciesForUser(User $user, string $systemCurrencyCode): void
    {
        $accounts = $user->accounts()
                         ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                         ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])
                         ->get(['accounts.*']);

        // get user's currency preference:
        $defaultCurrencyCode = app('preferences')->getForUser($user, 'currencyPreference', $systemCurrencyCode)->data;
        if (!is_string($defaultCurrencyCode)) {
            $defaultCurrencyCode = $systemCurrencyCode;
        }
        /** @var TransactionCurrency $defaultCurrency */
        $defaultCurrency = TransactionCurrency::where('code', $defaultCurrencyCode)->first();

        if (null === $defaultCurrency) {
            $this->error(sprintf('User has a preference for "%s", but this currency does not exist.', $defaultCurrencyCode));

            return;
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->updateAccount($account, $defaultCurrency);
        }
    }
}

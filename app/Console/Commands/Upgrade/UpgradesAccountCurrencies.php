<?php

/**
 * AccountCurrencies.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;

class UpgradesAccountCurrencies extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_account_currencies';

    protected $description          = 'Give all accounts proper currency info.';
    protected $signature            = 'upgrade:480-account-currencies {--F|force : Force the execution of this command.}';
    private AccountRepositoryInterface $accountRepos;
    private int                        $count;
    private UserRepositoryInterface    $userRepos;

    /**
     * Each (asset) account must have a reference to a preferred currency. If the account does not have one, it's
     * forced upon the account.
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->updateAccountCurrencies();

        if (0 !== $this->count) {
            $this->friendlyInfo(sprintf('Corrected %d account(s).', $this->count));
        }

        $this->markAsExecuted();

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->userRepos    = app(UserRepositoryInterface::class);
        $this->count        = 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);

        return (bool) $configVar?->data;
    }

    private function updateAccountCurrencies(): void
    {
        $users = $this->userRepos->all();
        foreach ($users as $user) {
            $this->updateCurrenciesForUser($user);
        }
    }

    /**
     * @throws FireflyException
     */
    private function updateCurrenciesForUser(User $user): void
    {
        $this->accountRepos->setUser($user);
        $accounts        = $this->accountRepos->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        // get user's currency preference:
        $defaultCurrency = app('amount')->getDefaultCurrencyByUserGroup($user->userGroup);

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->updateAccount($account, $defaultCurrency);
        }
    }

    private function updateAccount(Account $account, TransactionCurrency $currency): void
    {
        $this->accountRepos->setUser($account->user);
        $accountCurrency = (int) $this->accountRepos->getMetaValue($account, 'currency_id');
        $openingBalance  = $this->accountRepos->getOpeningBalance($account);
        $obCurrency      = (int) $openingBalance?->transaction_currency_id;

        // both 0? set to default currency:
        if (0 === $accountCurrency && 0 === $obCurrency) {
            AccountMeta::where('account_id', $account->id)->where('name', 'currency_id')->forceDelete();
            AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $currency->id]);
            $this->friendlyInfo(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $currency->code));
            ++$this->count;

            return;
        }

        // account is set to 0, opening balance is not?
        if (0 === $accountCurrency && $obCurrency > 0) {
            AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $obCurrency]);
            $this->friendlyInfo(sprintf('Account #%d ("%s") now has a currency setting (#%d).', $account->id, $account->name, $obCurrency));
            ++$this->count;

            return;
        }
        // do not match and opening balance id is not null.
        if ($accountCurrency !== $obCurrency && null !== $openingBalance) {
            // update opening balance:
            $openingBalance->transaction_currency_id = $accountCurrency;
            $openingBalance->save();
            $openingBalance->transactions->each(
                static function (Transaction $transaction) use ($accountCurrency): void {
                    $transaction->transaction_currency_id = $accountCurrency;
                    $transaction->save();
                }
            );
            $this->friendlyInfo(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));
            ++$this->count;
        }
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

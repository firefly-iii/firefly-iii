<?php


/*
 * UpgradesMultiPiggyBanks.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpgradesMultiPiggyBanks extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '620_make_multi_piggies';

    protected $description          = 'Upgrade piggy banks so they can use multiple accounts.';

    protected $signature            = 'upgrade:620-piggy-banks {--F|force : Force the execution of this command.}';
    private AccountRepositoryInterface   $accountRepository;
    private PiggyBankRepositoryInterface $repository;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->upgradePiggyBanks();
        $this->friendlyInfo('Upgraded all piggy banks.');

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

    private function upgradePiggyBanks(): void
    {
        $this->repository        = app(PiggyBankRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $set                     = PiggyBank::whereNotNull('account_id')->get();
        Log::debug(sprintf('Will update %d piggy banks(s).', $set->count()));

        /** @var PiggyBank $piggyBank */
        foreach ($set as $piggyBank) {
            $this->upgradePiggyBank($piggyBank);
        }
    }

    private function upgradePiggyBank(PiggyBank $piggyBank): void
    {
        if(null === $piggyBank->account) {
            // #10432 account has been deleted, delete piggy bank.
            $piggyBank->delete();
            return;
        }
        $this->repository->setUser($piggyBank->account->user);
        $this->accountRepository->setUser($piggyBank->account->user);
        $repetition                         = $this->repository->getRepetition($piggyBank, true);
        $currency                           = $this->accountRepository->getAccountCurrency($piggyBank->account) ?? app('amount')->getNativeCurrencyByUserGroup($piggyBank->account->user->userGroup);

        // update piggy bank to have a currency.
        $piggyBank->transaction_currency_id = $currency->id;
        $piggyBank->saveQuietly();

        // store current amount in account association.
        $piggyBank->accounts()->sync([$piggyBank->account->id => ['current_amount' => $repetition->current_amount]]);
        $piggyBank->account_id              = null;
        $piggyBank->saveQuietly();

        // remove all repetitions (no longer used)
        $piggyBank->piggyBankRepetitions()->delete();

    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

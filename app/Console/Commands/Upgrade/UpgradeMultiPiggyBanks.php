<?php
/*
 * UpgradeMultiPiggyBanks.php
 * Copyright (c) 2024 james@firefly-iii.org.
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpgradeMultiPiggyBanks extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '620_make_multi_piggies';

    protected $description = 'Upgrade piggybanks so they can use multiple accounts.';

    protected $signature = 'firefly-iii:upgrade-multi-piggies {--F|force : Force the execution of this command.}';

    private PiggyBankRepositoryInterface $repository;
    private AccountRepositoryInterface   $accountRepository;

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
        $this->upgradePiggyBanks();
        $this->friendlyInfo('Upgraded all piggy banks.');

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
            return (bool) $configVar->data;
        }

        return false;
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
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
        $this->repository->setUser($piggyBank->account->user);
        $this->accountRepository->setUser($piggyBank->account->user);
        $repetition = $this->repository->getRepetition($piggyBank);
        $currency   = $this->accountRepository->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrencyByUserGroup($piggyBank->account->user->userGroup);

        // update piggy bank to have a currency.
        $piggyBank->transaction_currency_id = $currency->id;
        $piggyBank->save();

        // store current amount in account association.
        $piggyBank->accounts()->sync([$piggyBank->account->id => ['current_amount' => $repetition->current_amount]]);
        $piggyBank->account_id = null;
        $piggyBank->save();

        // remove all repetitions (no longer used)
        $piggyBank->piggyBankRepetitions()->delete();

    }
}

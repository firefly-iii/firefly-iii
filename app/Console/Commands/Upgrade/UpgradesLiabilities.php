<?php

/*
 * UpgradeLiabilities.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\User;
use Illuminate\Console\Command;

class UpgradesLiabilities extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '560_upgrade_liabilities';
    protected $description          = 'Upgrade liabilities to new 5.6.0 structure.';
    protected $signature            = 'upgrade:560-liabilities {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->upgradeLiabilities();

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

    private function upgradeLiabilities(): void
    {
        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $this->upgradeForUser($user);
        }
    }

    private function upgradeForUser(User $user): void
    {
        $accounts = $user->accounts()
            ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->whereIn('account_types.type', config('firefly.valid_liabilities'))
            ->get(['accounts.*'])
        ;

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->upgradeLiability($account);
            $service = app(CreditRecalculateService::class);
            $service->setAccount($account);
            $service->recalculate();
        }
    }

    private function upgradeLiability(Account $account): void
    {
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $repository->setUser($account->user);

        // get opening balance, and correct if necessary.
        $openingBalance = $repository->getOpeningBalance($account);
        if (null !== $openingBalance) {
            // correct if necessary
            $this->correctOpeningBalance($account, $openingBalance);
        }

        // add liability direction property (if it does not yet exist!)
        $value          = $repository->getMetaValue($account, 'liability_direction');
        if (null === $value) {
            /** @var AccountMetaFactory $factory */
            $factory = app(AccountMetaFactory::class);
            $factory->crud($account, 'liability_direction', 'debit');
        }
    }

    private function correctOpeningBalance(Account $account, TransactionJournal $openingBalance): void
    {
        $source      = $this->getSourceTransaction($openingBalance);
        $destination = $this->getDestinationTransaction($openingBalance);
        if (!$source instanceof Transaction || !$destination instanceof Transaction) {
            return;
        }
        // source MUST be the liability.
        if ($destination->account_id === $account->id) {
            // so if not, switch things around:
            $sourceAccountId         = $source->account_id;
            $source->account_id      = $destination->account_id;
            $destination->account_id = $sourceAccountId;
            $source->save();
            $destination->save();
        }
    }

    private function getSourceTransaction(TransactionJournal $journal): ?Transaction
    {
        /** @var null|Transaction */
        return $journal->transactions()->where('amount', '<', 0)->first();
    }

    private function getDestinationTransaction(TransactionJournal $journal): ?Transaction
    {
        /** @var null|Transaction */
        return $journal->transactions()->where('amount', '>', 0)->first();
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

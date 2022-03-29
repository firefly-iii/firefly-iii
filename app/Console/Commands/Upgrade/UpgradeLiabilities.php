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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\User;
use Illuminate\Console\Command;
use Log;

/**
 * Class UpgradeLiabilities
 */
class UpgradeLiabilities extends Command
{
    public const CONFIG_NAME = '560_upgrade_liabilities';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade liabilities to new 5.6.0 structure.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:upgrade-liabilities {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        $this->upgradeLiabilities();

        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Upgraded liabilities in %s seconds.', $end));

        return 0;
    }

    /**
     * @return bool
     * @throws FireflyException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
    private function upgradeLiabilities(): void
    {
        Log::debug('Upgrading liabilities.');
        $users = User::get();
        /** @var User $user */
        foreach ($users as $user) {
            $this->upgradeForUser($user);
        }
    }

    /**
     * @param User $user
     */
    private function upgradeForUser(User $user): void
    {
        Log::debug(sprintf('Upgrading liabilities for user #%d', $user->id));
        $accounts = $user->accounts()
                         ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                         ->whereIn('account_types.type', config('firefly.valid_liabilities'))
                         ->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->upgradeLiability($account);
            $service = app(CreditRecalculateService::class);
            $service->setAccount($account);
            $service->recalculate();
        }
    }

    /**
     * @param Account $account
     */
    private function upgradeLiability(Account $account): void
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($account->user);
        Log::debug(sprintf('Upgrade liability #%d', $account->id));

        // get opening balance, and correct if necessary.
        $openingBalance = $repository->getOpeningBalance($account);
        if (null !== $openingBalance) {
            // correct if necessary
            $this->correctOpeningBalance($account, $openingBalance);
        }

        // add liability direction property
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        $factory->crud($account, 'liability_direction', 'debit');
    }

    /**
     * @param Account            $account
     * @param TransactionJournal $openingBalance
     */
    private function correctOpeningBalance(Account $account, TransactionJournal $openingBalance): void
    {
        $source      = $this->getSourceTransaction($openingBalance);
        $destination = $this->getDestinationTransaction($openingBalance);
        if (null === $source || null === $destination) {
            return;
        }
        // source MUST be the liability.
        if ((int) $destination->account_id === (int) $account->id) {
            Log::debug(sprintf('Must switch around, because account #%d is the destination.', $destination->account_id));
            // so if not, switch things around:
            $sourceAccountId         = (int) $source->account_id;
            $source->account_id      = $destination->account_id;
            $destination->account_id = $sourceAccountId;
            $source->save();
            $destination->save();
            Log::debug(sprintf('Source transaction #%d now has account #%d', $source->id, $source->account_id));
            Log::debug(sprintf('Dest   transaction #%d now has account #%d', $destination->id, $destination->account_id));
        }
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    private function getSourceTransaction(TransactionJournal $journal): ?Transaction
    {
        return $journal->transactions()->where('amount', '<', 0)->first();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    private function getDestinationTransaction(TransactionJournal $journal): ?Transaction
    {
        return $journal->transactions()->where('amount', '>', 0)->first();
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

}

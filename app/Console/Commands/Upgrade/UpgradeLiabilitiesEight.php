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
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class UpgradeLiabilitiesEight
 */
class UpgradeLiabilitiesEight extends Command
{
    public const CONFIG_NAME = '600_upgrade_liabilities';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade liabilities to new 6.0.0 structure.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:liabilities-600 {--F|force : Force the execution of this command.}';

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
        $this->info(sprintf('Upgraded liabilities for 6.0.0 in %s seconds.', $end));

        return 0;
    }

    /**
     * @return bool
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @param  User  $user
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
     * @param  Account  $account
     */
    private function upgradeLiability(Account $account): void
    {
        Log::debug(sprintf('Upgrade liability #%d ("%s")', $account->id, $account->name));

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($account->user);

        $direction = $repository->getMetaValue($account, 'liability_direction');
        if ('debit' === $direction) {
            Log::debug('Direction is debit ("I owe this amount"), so no need to upgrade.');
        }
        if ('credit' === $direction && $this->hasBadOpening($account)) {
            $this->deleteCreditTransaction($account);
            $this->reverseOpeningBalance($account);
            $this->line(sprintf('Corrected opening balance for liability #%d ("%s")', $account->id, $account->name));
        }
        if ('credit' === $direction) {
            $count = $this->deleteTransactions($account);
            if ($count > 0) {
                $this->line(sprintf('Removed %d old format transaction(s) for liability #%d ("%s")', $count, $account->id, $account->name));
            }
        }
        Log::debug(sprintf('Done upgrading liability #%d ("%s")', $account->id, $account->name));
    }

    /**
     * @param $account
     * @return void
     */
    private function deleteTransactions($account): int
    {
        $count    = 0;
        $journals = TransactionJournal::leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                      ->where('transactions.account_id', $account->id)->get(['transaction_journals.*']);
        Log::debug(sprintf('Found %d journals to analyse.', $journals->count()));
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $delete = false;
            /** @var Transaction $source */
            $source = $journal->transactions()->where('amount', '<', 0)->first();
            /** @var Transaction $dest */
            $dest = $journal->transactions()->where('amount', '>', 0)->first();

            // if source is this liability and destination is expense, remove transaction.
            // if source is revenue and destination is liability, remove transaction.
            if ((int)$source->account_id === (int)$account->id && $dest->account->accountType->type === AccountType::EXPENSE) {
                $delete = true;
            }
            if ((int)$dest->account_id === (int)$account->id && $source->account->accountType->type === AccountType::REVENUE) {
                $delete = true;
            }
            if ($delete) {
                Log::debug(
                    sprintf(
                        'Deleted %s journal #%d ("%s") (%s %s).',
                        $journal->transactionType->type,
                        $journal->id,
                        $journal->description,
                        $journal->transactionCurrency->code,
                        $dest->amount
                    )
                );
                $service = app(TransactionGroupDestroyService::class);
                $service->destroy($journal->transactionGroup);
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param  Account  $account
     * @return void
     */
    private function deleteCreditTransaction(Account $account): void
    {
        Log::debug('Will delete credit transaction.');
        $liabilityType    = TransactionType::whereType(TransactionType::LIABILITY_CREDIT)->first();
        $liabilityJournal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                              ->where('transactions.account_id', $account->id)
                                              ->where('transaction_journals.transaction_type_id', $liabilityType->id)
                                              ->first(['transaction_journals.*']);
        if (null !== $liabilityJournal) {
            $group   = $liabilityJournal->transactionGroup;
            $service = new TransactionGroupDestroyService();
            $service->destroy($group);
            Log::debug(sprintf('Deleted liability credit group #%d', $group->id));
            return;
        }
        Log::debug('No liability credit journal found.');
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    /**
     * @param  Account  $account
     * @return bool
     */
    private function hasBadOpening(Account $account): bool
    {
        $openingBalanceType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $liabilityType      = TransactionType::whereType(TransactionType::LIABILITY_CREDIT)->first();
        $openingJournal     = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                                ->where('transactions.account_id', $account->id)
                                                ->where('transaction_journals.transaction_type_id', $openingBalanceType->id)
                                                ->first(['transaction_journals.*']);
        if (null === $openingJournal) {
            Log::debug('Account has no opening balance and can be skipped.');
            return false;
        }
        $liabilityJournal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                              ->where('transactions.account_id', $account->id)
                                              ->where('transaction_journals.transaction_type_id', $liabilityType->id)
                                              ->first(['transaction_journals.*']);
        if (null === $liabilityJournal) {
            Log::debug('Account has no liability credit and can be skipped.');
            return false;
        }
        if (!$openingJournal->date->isSameDay($liabilityJournal->date)) {
            Log::debug('Account has opening/credit not on the same day.');
            return false;
        }
        Log::debug('Account has bad opening balance data.');

        return true;
    }

    /**
     * @param  Account  $account
     * @return void
     */
    private function reverseOpeningBalance(Account $account): void
    {
        $openingBalanceType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        /** @var TransactionJournal $openingJournal */
        $openingJournal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                            ->where('transactions.account_id', $account->id)
                                            ->where('transaction_journals.transaction_type_id', $openingBalanceType->id)
                                            ->first(['transaction_journals.*']);
        /** @var Transaction $source */
        $source = $openingJournal->transactions()->where('amount', '<', 0)->first();
        /** @var Transaction $dest */
        $dest = $openingJournal->transactions()->where('amount', '>', 0)->first();
        if ($source && $dest) {
            $sourceId           = $source->account_id;
            $destId             = $dest->account_id;
            $dest->account_id   = $sourceId;
            $source->account_id = $destId;
            $source->save();
            $dest->save();
            Log::debug(sprintf('Opening balance transaction journal #%d reversed.', $openingJournal->id));
            return;
        }
        Log::warning('Did not find opening balance.');
    }
}

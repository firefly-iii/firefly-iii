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
    use ShowsFriendlyMessages;

    public const CONFIG_NAME = '600_upgrade_liabilities';
    protected $description = 'Upgrade liabilities to new 6.0.0 structure.';
    protected $signature   = 'firefly-iii:liabilities-600 {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

    /**
     * @return bool
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

        $direction = $repository->getMetaValue($account, 'liability_direction');
        if ('credit' === $direction && $this->hasBadOpening($account)) {
            $this->deleteCreditTransaction($account);
            $this->reverseOpeningBalance($account);
            $this->friendlyInfo(sprintf('Corrected opening balance for liability #%d ("%s")', $account->id, $account->name));
        }
        if ('credit' === $direction) {
            $count = $this->deleteTransactions($account);
            if ($count > 0) {
                $this->friendlyInfo(sprintf('Removed %d old format transaction(s) for liability #%d ("%s")', $count, $account->id, $account->name));
            }
        }
    }

    /**
     * @param Account $account
     *
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
            return false;
        }
        $liabilityJournal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                              ->where('transactions.account_id', $account->id)
                                              ->where('transaction_journals.transaction_type_id', $liabilityType->id)
                                              ->first(['transaction_journals.*']);
        if (null === $liabilityJournal) {
            return false;
        }
        if (!$openingJournal->date->isSameDay($liabilityJournal->date)) {
            return false;
        }

        return true;
    }

    /**
     * @param Account $account
     *
     * @return void
     */
    private function deleteCreditTransaction(Account $account): void
    {
        $liabilityType    = TransactionType::whereType(TransactionType::LIABILITY_CREDIT)->first();
        $liabilityJournal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                              ->where('transactions.account_id', $account->id)
                                              ->where('transaction_journals.transaction_type_id', $liabilityType->id)
                                              ->first(['transaction_journals.*']);
        if (null !== $liabilityJournal) {
            $group   = $liabilityJournal->transactionGroup;
            $service = new TransactionGroupDestroyService();
            $service->destroy($group);

            return;
        }
    }

    /**
     * @param Account $account
     *
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
        /** @var Transaction|null $source */
        $source = $openingJournal->transactions()->where('amount', '<', 0)->first();
        /** @var Transaction|null $dest */
        $dest = $openingJournal->transactions()->where('amount', '>', 0)->first();
        if ($source && $dest) {
            $sourceId           = $source->account_id;
            $destId             = $dest->account_id;
            $dest->account_id   = $sourceId;
            $source->account_id = $destId;
            $source->save();
            $dest->save();

            return;
        }
        Log::warning('Did not find opening balance.');
    }

    /**
     * @param $account
     *
     * @return int
     */
    private function deleteTransactions($account): int
    {
        $count    = 0;
        $journals = TransactionJournal::leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                      ->where('transactions.account_id', $account->id)->get(['transaction_journals.*']);
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

            // overruled. No transaction will be deleted, ever.
            // code is kept in place so i can revisit my reasoning.
            $delete = false;

            if ($delete) {
                $service = app(TransactionGroupDestroyService::class);
                $service->destroy($journal->transactionGroup);
                $count++;
            }
        }

        return $count;
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}

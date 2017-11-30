<?php
/**
 * VerifyDatabase.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Crypt;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Preferences;
use Schema;
use stdClass;

/**
 * Class VerifyDatabase.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VerifyDatabase extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will verify your database.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:verify';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // if table does not exist, return false
        if (!Schema::hasTable('users')) {
            return;
        }

        $this->reportObject('budget');
        $this->reportObject('category');
        $this->reportObject('tag');
        $this->reportAccounts();
        $this->reportBudgetLimits();
        $this->reportSum();
        $this->reportJournals();
        $this->reportTransactions();
        $this->reportDeletedAccounts();
        $this->reportNoTransactions();
        $this->reportTransfersBudgets();
        $this->reportIncorrectJournals();
        $this->repairPiggyBanks();
        $this->createLinkTypes();
        $this->createAccessTokens();
    }

    /**
     * Create user access tokens, if not present already.
     */
    private function createAccessTokens()
    {
        $users = User::get();
        /** @var User $user */
        foreach ($users as $user) {
            $pref = Preferences::getForUser($user, 'access_token', null);
            if (null === $pref) {
                $token = $user->generateAccessToken();
                Preferences::setForUser($user, 'access_token', $token);
                $this->line(sprintf('Generated access token for user %s', $user->email));
            }
        }
    }

    /**
     * Create default link types if necessary.
     */
    private function createLinkTypes()
    {
        $set = [
            'Related'       => ['relates to', 'relates to'],
            'Refund'        => ['(partially) refunds', 'is (partially) refunded by'],
            'Paid'          => ['(partially) pays for', 'is (partially) paid for by'],
            'Reimbursement' => ['(partially) reimburses', 'is (partially) reimbursed by'],
        ];
        foreach ($set as $name => $values) {
            $link = LinkType::where('name', $name)->where('outward', $values[0])->where('inward', $values[1])->first();
            if (null === $link) {
                $link          = new LinkType;
                $link->name    = $name;
                $link->outward = $values[0];
                $link->inward  = $values[1];
            }
            $link->editable = false;
            $link->save();
        }
    }

    /**
     * Eeport (and fix) piggy banks. Make sure there are only transfers linked to piggy bank events.
     */
    private function repairPiggyBanks(): void
    {
        $set = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal', 'TransactionJournal.TransactionType'])->get();
        $set->each(
            function (PiggyBankEvent $event) {
                if (null === $event->transaction_journal_id) {
                    return true;
                }
                /** @var TransactionJournal $journal */
                $journal = $event->transactionJournal()->first();
                if (null === $journal) {
                    return true;
                }

                $type = $journal->transactionType->type;
                if (TransactionType::TRANSFER !== $type) {
                    $event->transaction_journal_id = null;
                    $event->save();
                    $this->line(sprintf('Piggy bank #%d was referenced by an invalid event. This has been fixed.', $event->piggy_bank_id));
                }

                return true;
            }
        );

        return;
    }

    /**
     * Reports on accounts with no transactions.
     */
    private function reportAccounts()
    {
        $set = Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                      ->leftJoin('users', 'accounts.user_id', '=', 'users.id')
                      ->groupBy(['accounts.id', 'accounts.encrypted', 'accounts.name', 'accounts.user_id', 'users.email'])
                      ->whereNull('transactions.account_id')
                      ->get(
                          ['accounts.id', 'accounts.encrypted', 'accounts.name', 'accounts.user_id', 'users.email']
                      );

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $name = $entry->name;
            $line = 'User #%d (%s) has account #%d ("%s") which has no transactions.';
            $line = sprintf($line, $entry->user_id, $entry->email, $entry->id, $name);
            $this->line($line);
        }
    }

    /**
     * Reports on budgets with no budget limits (which makes them pointless).
     */
    private function reportBudgetLimits()
    {
        $set = Budget::leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budgets.id')
                     ->leftJoin('users', 'budgets.user_id', '=', 'users.id')
                     ->groupBy(['budgets.id', 'budgets.name', 'budgets.encrypted', 'budgets.user_id', 'users.email'])
                     ->whereNull('budget_limits.id')
                     ->get(['budgets.id', 'budgets.name', 'budgets.user_id', 'budgets.encrypted', 'users.email']);

        /** @var Budget $entry */
        foreach ($set as $entry) {
            $line = sprintf(
                'User #%d (%s) has budget #%d ("%s") which has no budget limits.',
                $entry->user_id,
                $entry->email,
                $entry->id,
                $entry->name
            );
            $this->line($line);
        }
    }

    /**
     * Reports on deleted accounts that still have not deleted transactions or journals attached to them.
     */
    private function reportDeletedAccounts()
    {
        $set = Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                      ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                      ->whereNotNull('accounts.deleted_at')
                      ->whereNotNull('transactions.id')
                      ->where(
                          function (Builder $q) {
                              $q->whereNull('transactions.deleted_at');
                              $q->orWhereNull('transaction_journals.deleted_at');
                          }
                      )
                      ->get(
                          ['accounts.id as account_id', 'accounts.deleted_at as account_deleted_at', 'transactions.id as transaction_id',
                           'transactions.deleted_at as transaction_deleted_at', 'transaction_journals.id as journal_id',
                           'transaction_journals.deleted_at as journal_deleted_at',]
                      );
        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $date = null === $entry->transaction_deleted_at ? $entry->journal_deleted_at : $entry->transaction_deleted_at;
            $this->error(
                'Error: Account #' . $entry->account_id . ' should have been deleted, but has not.' .
                ' Find it in the table called "accounts" and change the "deleted_at" field to: "' . $date . '"'
            );
        }
    }

    /**
     * Report on journals with bad account types linked to them.
     */
    private function reportIncorrectJournals()
    {
        $configuration = [
            // a withdrawal can not have revenue account:
            TransactionType::WITHDRAWAL => [AccountType::REVENUE],
            // deposit cannot have an expense account:
            TransactionType::DEPOSIT    => [AccountType::EXPENSE],
            // transfer cannot have either:
            TransactionType::TRANSFER   => [AccountType::EXPENSE, AccountType::REVENUE],
        ];
        foreach ($configuration as $transactionType => $accountTypes) {
            $set = TransactionJournal::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                     ->leftJoin('account_types', 'account_types.id', 'accounts.account_type_id')
                                     ->leftJoin('users', 'users.id', '=', 'transaction_journals.user_id')
                                     ->where('transaction_types.type', $transactionType)
                                     ->whereIn('account_types.type', $accountTypes)
                                     ->whereNull('transaction_journals.deleted_at')
                                     ->get(
                                         ['transaction_journals.id', 'transaction_journals.user_id', 'users.email', 'account_types.type as a_type',
                                          'transaction_types.type',]
                                     );
            foreach ($set as $entry) {
                $this->error(
                    sprintf(
                        'Transaction journal #%d (user #%d, %s) is of type "%s" but ' .
                        'is linked to a "%s". The transaction journal should be recreated.',
                        $entry->id,
                        $entry->user_id,
                        $entry->email,
                        $entry->type,
                        $entry->a_type
                    )
                );
            }
        }
    }

    /**
     * Any deleted transaction journals that have transactions that are NOT deleted:.
     */
    private function reportJournals()
    {
        $set = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                 ->whereNotNull('transaction_journals.deleted_at')// USE THIS
                                 ->whereNull('transactions.deleted_at')
                                 ->whereNotNull('transactions.id')
                                 ->get(
                                     [
                                         'transaction_journals.id as journal_id',
                                         'transaction_journals.description',
                                         'transaction_journals.deleted_at as journal_deleted',
                                         'transactions.id as transaction_id',
                                         'transactions.deleted_at as transaction_deleted_at',]
                                 );
        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $this->error(
                'Error: Transaction #' . $entry->transaction_id . ' should have been deleted, but has not.' .
                ' Find it in the table called "transactions" and change the "deleted_at" field to: "' . $entry->journal_deleted . '"'
            );
        }
    }

    /**
     * Report on journals without transactions.
     */
    private function reportNoTransactions()
    {
        $set = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                 ->groupBy('transaction_journals.id')
                                 ->whereNull('transactions.transaction_journal_id')
                                 ->get(['transaction_journals.id']);

        foreach ($set as $entry) {
            $this->error(
                'Error: Journal #' . $entry->id . ' has zero transactions. Open table "transaction_journals" and delete the entry with id #' . $entry->id
            );
        }
    }

    /**
     * Report on things with no linked journals.
     *
     * @param string $name
     */
    private function reportObject(string $name)
    {
        $plural = str_plural($name);
        $class  = sprintf('FireflyIII\Models\%s', ucfirst($name));
        $field  = 'tag' === $name ? 'tag' : 'name';
        $set    = $class::leftJoin($name . '_transaction_journal', $plural . '.id', '=', $name . '_transaction_journal.' . $name . '_id')
                        ->leftJoin('users', $plural . '.user_id', '=', 'users.id')
                        ->distinct()
                        ->whereNull($name . '_transaction_journal.' . $name . '_id')
                        ->whereNull($plural . '.deleted_at')
                        ->get([$plural . '.id', $plural . '.' . $field . ' as name', $plural . '.user_id', 'users.email']);

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $objName = $entry->name;
            try {
                $objName = Crypt::decrypt($objName);
            } catch (DecryptException $e) {
                // it probably was not encrypted.
            }

            $line = sprintf(
                'User #%d (%s) has %s #%d ("%s") which has no transactions.',
                $entry->user_id,
                $entry->email,
                $name,
                $entry->id,
                $objName
            );
            $this->line($line);
        }
    }

    /**
     * Reports for each user when the sum of their transactions is not zero.
     */
    private function reportSum()
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = app(UserRepositoryInterface::class);

        /** @var User $user */
        foreach ($userRepository->all() as $user) {
            $sum = strval($user->transactions()->sum('amount'));
            if (0 !== bccomp($sum, '0')) {
                $this->error('Error: Transactions for user #' . $user->id . ' (' . $user->email . ') are off by ' . $sum . '!');
            }
        }
    }

    /**
     * Reports on deleted transactions that are connected to a not deleted journal.
     */
    private function reportTransactions()
    {
        $set = Transaction::leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->whereNotNull('transactions.deleted_at')
                          ->whereNull('transaction_journals.deleted_at')
                          ->get(
                              ['transactions.id as transaction_id', 'transactions.deleted_at as transaction_deleted', 'transaction_journals.id as journal_id',
                               'transaction_journals.deleted_at',]
                          );
        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $this->error(
                'Error: Transaction journal #' . $entry->journal_id . ' should have been deleted, but has not.' .
                ' Find it in the table called "transaction_journals" and change the "deleted_at" field to: "' . $entry->transaction_deleted . '"'
            );
        }
    }

    /**
     * Report on transfers that have budgets.
     */
    private function reportTransfersBudgets()
    {
        $set = TransactionJournal::distinct()
                                 ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                 ->leftJoin('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
                                 ->whereIn('transaction_types.type', [TransactionType::TRANSFER, TransactionType::DEPOSIT])
                                 ->whereNotNull('budget_transaction_journal.budget_id')->get(['transaction_journals.*']);

        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $this->error(
                sprintf(
                    'Error: Transaction journal #%d is a %s, but has a budget. Edit it without changing anything, so the budget will be removed.',
                    $entry->id,
                    $entry->transactionType->type
                )
            );
        }
    }
}

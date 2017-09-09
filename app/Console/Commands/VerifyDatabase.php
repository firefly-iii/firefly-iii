<?php
/**
 * VerifyDatabase.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
use Schema;
use stdClass;

/**
 * Class VerifyDatabase
 *
 * @package FireflyIII\Console\Commands
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

        // accounts with no transactions.
        $this->reportAccounts();
        // budgets with no limits
        $this->reportBudgetLimits();
        // budgets with no transactions

        // sum of transactions is not zero.
        $this->reportSum();
        //  any deleted transaction journals that have transactions that are NOT deleted:
        $this->reportJournals();
        // deleted transactions that are connected to a not deleted journal.
        $this->reportTransactions();
        // deleted accounts that still have not deleted transactions or journals attached to them.
        $this->reportDeletedAccounts();

        // report on journals with no transactions at all.
        $this->reportNoTransactions();

        // transfers with budgets.
        $this->reportTransfersBudgets();

        // report on journals with the wrong types of accounts.
        $this->reportIncorrectJournals();

        // report (and fix) piggy banks
        $this->repairPiggyBanks();

        // create default link types if necessary
        $this->createLinkTypes();

    }

    /**
     *
     */
    private function createLinkTypes()
    {
        $set = [
            'Relates'       => ['relates to', 'relates to'],
            'Refund'        => ['(partially) refunds', 'is (partially) refunded by'],
            'Paid'          => ['(partially) pays for', 'is (partially) paid for by'],
            'Reimbursement' => ['(partially) reimburses', 'is (partially) reimbursed by'],
        ];
        foreach ($set as $name => $values) {
            $link = LinkType::where('name', $name)->where('outward', $values[0])->where('inward', $values[1])->first();
            if (is_null($link)) {
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
     * Make sure there are only transfers linked to piggy bank events.
     */
    private function repairPiggyBanks(): void
    {
        $set = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal', 'TransactionJournal.TransactionType'])->get();
        $set->each(
            function (PiggyBankEvent $event) {
                if (is_null($event->transaction_journal_id)) {
                    return true;
                }
                /** @var TransactionJournal $journal */
                $journal = $event->transactionJournal()->first();
                if (is_null($journal)) {
                    return true;
                }

                $type = $journal->transactionType->type;
                if ($type !== TransactionType::TRANSFER) {
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
                $entry->user_id, $entry->email, $entry->id, $entry->name
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
                           'transaction_journals.deleted_at as journal_deleted_at']
                      );
        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $date = is_null($entry->transaction_deleted_at) ? $entry->journal_deleted_at : $entry->transaction_deleted_at;
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
                                          'transaction_types.type']
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
     * Any deleted transaction journals that have transactions that are NOT deleted:
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
                                         'transactions.deleted_at as transaction_deleted_at']
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
        $field  = $name === 'tag' ? 'tag' : 'name';
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
                $entry->user_id, $entry->email, $name, $entry->id, $objName
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
            if (bccomp($sum, '0') !== 0) {
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
                               'transaction_journals.deleted_at']
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
                                 ->where('transaction_types.type', TransactionType::TRANSFER)
                                 ->whereNotNull('budget_transaction_journal.budget_id')->get(['transaction_journals.id']);

        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $this->error(
                sprintf(
                    'Error: Transaction journal #%d is a transfer, but has a budget. Edit it without changing anything, so the budget will be removed.',
                    $entry->id
                )
            );
        }


    }
}

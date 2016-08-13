<?php
/**
 * VerifyDatabase.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;

use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
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
     *
     * @return mixed
     */
    public function handle()
    {
        // accounts with no transactions.
        $this->reportAccounts();
        // budgets with no limits
        $this->reportBudgetLimits();
        // budgets with no transactions
        $this->reportBudgets();
        // categories with no transactions
        $this->reportCategories();
        // tags with no transactions
        $this->reportTags();
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
    }

    /**
     * Reports on accounts with no transactions.
     */
    private function reportAccounts()
    {
        $set = Account
            ::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('users', 'accounts.user_id', '=', 'users.id')
            ->groupBy('accounts.id')
            ->having('transaction_count', '=', 0)
            ->get(
                ['accounts.id', 'accounts.encrypted', 'accounts.name', 'accounts.user_id', 'users.email',
                 DB::raw('COUNT(`transactions`.`id`) AS `transaction_count`')]
            );

        /** @var stdClass $entry */
        foreach ($set as $entry) {

            $isEncrypted = intval($entry->encrypted) === 1;
            $name        = $entry->name;
            if ($isEncrypted) {
                $name = Crypt::decrypt($entry->name);
            }
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
        $set = Budget
            ::leftJoin('budget_limits', 'budget_limits.budget_id', '=', 'budgets.id')
            ->leftJoin('users', 'budgets.user_id', '=', 'users.id')
            ->groupBy('budgets.id')
            ->having('budget_limit_count', '=', 0)
            ->get(['budgets.id', 'budgets.name', 'budgets.user_id', 'users.email', DB::raw('COUNT(`budget_limits`.`id`) AS `budget_limit_count`')]);

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $line = 'Notice: User #' . $entry->user_id . ' (' . $entry->email . ') has budget #' . $entry->id . ' ("' . Crypt::decrypt($entry->name)
                    . '") which has no budget limits.';
            $this->line($line);
        }
    }

    /**
     * Reports on budgets without any transactions.
     */
    private function reportBudgets()
    {
        $set = Budget
            ::leftJoin('budget_transaction_journal', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
            ->leftJoin('users', 'budgets.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('budget_transaction_journal.budget_id')
            ->whereNull('budgets.deleted_at')
            ->get(['budgets.id', 'budgets.name', 'budgets.user_id', 'users.email']);

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $line = 'Notice: User #' . $entry->user_id . ' (' . $entry->email . ') has budget #' . $entry->id . ' ("' . Crypt::decrypt($entry->name)
                    . '") which has no transactions.';
            $this->line($line);
        }
    }

    /**
     * Reports on categories without any transactions.
     */
    private function reportCategories()
    {
        $set = Category
            ::leftJoin('category_transaction_journal', 'categories.id', '=', 'category_transaction_journal.category_id')
            ->leftJoin('users', 'categories.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('category_transaction_journal.category_id')
            ->whereNull('categories.deleted_at')
            ->get(['categories.id', 'categories.name', 'categories.user_id', 'users.email']);

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $line = 'Notice: User #' . $entry->user_id . ' (' . $entry->email . ') has category #' . $entry->id . ' ("' . Crypt::decrypt($entry->name)
                    . '") which has no transactions.';
            $this->line($line);
        }
    }

    /**
     * Reports on deleted accounts that still have not deleted transactions or journals attached to them.
     */
    private function reportDeletedAccounts()
    {
        $set = Account
            ::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
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
                ' Find it in the table called `accounts` and change the `deleted_at` field to: "' . $date . '"'
            );
        }
    }

    /**
     * Any deleted transaction journals that have transactions that are NOT deleted:
     */
    private function reportJournals()
    {
        $set = TransactionJournal
            ::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
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
                ' Find it in the table called `transactions` and change the `deleted_at` field to: "' . $entry->journal_deleted . '"'
            );
        }
    }

    private function reportNoTransactions()
    {
        /*
         * select transaction_journals.id, count(transactions.id) as transaction_count from transaction_journals
left join transactions ON transaction_journals.id = transactions.transaction_journal_id
group by transaction_journals.id
having transaction_count = 0
         */
        $set = TransactionJournal
            ::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->groupBy('transaction_journals.id')
            ->having('transaction_count', '=', 0)
            ->get(['transaction_journals.id', DB::raw('COUNT(`transactions`.`id`) as `transaction_count`')]);

        foreach ($set as $entry) {
            $this->error(
                'Error: Journal #' . $entry->id . ' has zero transactions. Open table `transaction_journals` and delete the entry with id #' . $entry->id
            );
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
     * Reports on tags without any transactions.
     */
    private function reportTags()
    {
        $set = Tag
            ::leftJoin('tag_transaction_journal', 'tags.id', '=', 'tag_transaction_journal.tag_id')
            ->leftJoin('users', 'tags.user_id', '=', 'users.id')
            ->distinct()
            ->whereNull('tag_transaction_journal.tag_id')
            ->whereNull('tags.deleted_at')
            ->get(['tags.id', 'tags.tag', 'tags.user_id', 'users.email']);

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $line = 'Notice: User #' . $entry->user_id . ' (' . $entry->email . ') has tag #' . $entry->id . ' ("' . $entry->tag
                    . '") which has no transactions.';
            $this->line($line);
        }
    }

    /**
     * Reports on deleted transactions that are connected to a not deleted journal.
     */
    private function reportTransactions()
    {
        $set = Transaction
            ::leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
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
                ' Find it in the table called `transaction_journals` and change the `deleted_at` field to: "' . $entry->transaction_deleted . '"'
            );
        }
    }
}

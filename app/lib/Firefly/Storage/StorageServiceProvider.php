<?php
namespace Firefly\Storage;

use Illuminate\Support\ServiceProvider;

/**
 * Class StorageServiceProvider
 *
 * @package Firefly\Storage
 */
class StorageServiceProvider extends ServiceProvider
{


    /**
     * Triggered automatically by Laravel
     */
    public function register()
    {
        $this->app->bind(
                  'Firefly\Storage\User\UserRepositoryInterface',
                      'Firefly\Storage\User\EloquentUserRepository'
        );
        $this->app->bind(
                  'Firefly\Storage\Transaction\TransactionRepositoryInterface',
                      'Firefly\Storage\Transaction\EloquentTransactionRepository'
        );
        $this->app->bind(
                  'Firefly\Storage\Import\ImportRepositoryInterface',
                      'Firefly\Storage\Import\EloquentImportRepository'
        );


        $this->app->bind(
                  'Firefly\Storage\Piggybank\PiggybankRepositoryInterface',
                      'Firefly\Storage\Piggybank\EloquentPiggybankRepository'
        );

        $this->app->bind(
                  'Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface',
                      'Firefly\Storage\RecurringTransaction\EloquentRecurringTransactionRepository'
        );

        $this->app->bind(
                  'Firefly\Storage\Reminder\ReminderRepositoryInterface',
                      'Firefly\Storage\Reminder\EloquentReminderRepository'
        );

        $this->app->bind(
                  'Firefly\Storage\Account\AccountRepositoryInterface',
                      'Firefly\Storage\Account\EloquentAccountRepository'
        );
        $this->app->bind(
                  'Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface',
                      'Firefly\Storage\TransactionJournal\EloquentTransactionJournalRepository'
        );

        $this->app->bind(
                  'Firefly\Storage\Limit\LimitRepositoryInterface',
                      'Firefly\Storage\Limit\EloquentLimitRepository'
        );

        $this->app->bind(
                  'Firefly\Storage\Budget\BudgetRepositoryInterface',
                      'Firefly\Storage\Budget\EloquentBudgetRepository'
        );
        $this->app->bind(
                  'Firefly\Storage\Category\CategoryRepositoryInterface',
                      'Firefly\Storage\Category\EloquentCategoryRepository'
        );
    }

}
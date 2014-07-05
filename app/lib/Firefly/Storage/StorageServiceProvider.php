<?php
namespace Firefly\Storage;

use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{


    // Triggered automatically by Laravel
    public function register()
    {
        // storage:
        $this->app->bind(
            'Firefly\Storage\User\UserRepositoryInterface',
            'Firefly\Storage\User\EloquentUserRepository'
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
            'Firefly\Storage\Component\ComponentRepositoryInterface',
            'Firefly\Storage\Component\EloquentComponentRepository'
        );
    }

}
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
    }

}
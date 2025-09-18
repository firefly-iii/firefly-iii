<?php

/**
 * SafeTransactionServiceProvider.php
 * Copyright (c) 2024 james@firefly-iii.org
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

namespace FireflyIII\Providers;

use FireflyIII\Services\Budget\SafeBudgetService;
use FireflyIII\Services\Currency\SafeExchangeRateConverter;
use FireflyIII\Services\Internal\Update\GroupUpdateService;
use FireflyIII\Services\Internal\Update\SafeGroupUpdateService;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\ServiceProvider;

/**
 * Class SafeTransactionServiceProvider
 * 
 * Registers improved transaction services with proper database transaction
 * handling and rollback support.
 */
class SafeTransactionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Replace the standard ExchangeRateConverter with the safe version
        $this->app->bind(ExchangeRateConverter::class, function ($app) {
            return new SafeExchangeRateConverter();
        });
        
        // Replace the standard GroupUpdateService with the safe version
        $this->app->bind(GroupUpdateService::class, function ($app) {
            return new SafeGroupUpdateService();
        });
        
        // Register the safe budget service
        $this->app->singleton(SafeBudgetService::class, function ($app) {
            return new SafeBudgetService();
        });
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set up database configuration for better transaction handling
        $this->configureDatabaseForTransactions();
    }
    
    /**
     * Configure database settings for better transaction handling.
     */
    private function configureDatabaseForTransactions(): void
    {
        $connection = config('database.default');
        
        switch ($connection) {
            case 'mysql':
                // Set MySQL to use READ COMMITTED by default for better concurrency
                // while maintaining consistency
                config([
                    'database.connections.mysql.options' => array_merge(
                        config('database.connections.mysql.options', []),
                        [
                            \PDO::MYSQL_ATTR_INIT_COMMAND => 
                                "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'," .
                                "SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED",
                        ]
                    ),
                ]);
                break;
                
            case 'pgsql':
                // PostgreSQL already has good default transaction handling
                // but we can set additional options if needed
                config([
                    'database.connections.pgsql.options' => array_merge(
                        config('database.connections.pgsql.options', []),
                        [
                            '--client_encoding=UTF8',
                            '--default_transaction_isolation=read committed',
                        ]
                    ),
                ]);
                break;
                
            case 'sqlite':
                // Enable WAL mode for better concurrency in SQLite
                config([
                    'database.connections.sqlite.foreign_key_constraints' => true,
                ]);
                
                // Execute pragma commands after connection
                \DB::listen(function ($query) use ($connection) {
                    if ($connection === 'sqlite' && str_starts_with($query->sql, 'pragma')) {
                        return;
                    }
                    if ($connection === 'sqlite') {
                        \DB::statement('PRAGMA journal_mode=WAL');
                        \DB::statement('PRAGMA synchronous=NORMAL');
                        \DB::statement('PRAGMA foreign_keys=ON');
                    }
                });
                break;
        }
    }
}
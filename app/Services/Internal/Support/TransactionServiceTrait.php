<?php

/**
 * TransactionServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FireflyIII\Exceptions\FireflyException;

/**
 * Trait TransactionServiceTrait
 * 
 * Provides database transaction handling with proper rollback support
 * for all financial operations in Firefly III.
 */
trait TransactionServiceTrait
{
    /**
     * Execute a callback within a database transaction with retry logic.
     *
     * @param callable $callback The operation to execute
     * @param int $attempts Number of retry attempts for deadlock scenarios
     * @return mixed The result of the callback
     * @throws FireflyException
     */
    protected function executeInTransaction(callable $callback, int $attempts = 3): mixed
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $attempts) {
            try {
                return DB::transaction(function () use ($callback) {
                    // Set isolation level for financial transactions
                    DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
                    return $callback();
                }, 5); // Laravel's internal retry count
            } catch (\PDOException $e) {
                $lastException = $e;
                
                // Check for deadlock or lock timeout
                if ($this->isDeadlock($e) && $attempt < $attempts) {
                    Log::warning(sprintf(
                        'Transaction deadlock detected (attempt %d/%d): %s',
                        $attempt,
                        $attempts,
                        $e->getMessage()
                    ));
                    
                    // Exponential backoff
                    usleep((int) (100000 * pow(2, $attempt - 1))); // 100ms, 200ms, 400ms
                    $attempt++;
                    continue;
                }
                
                // Not a deadlock or final attempt
                Log::error('Transaction failed: ' . $e->getMessage());
                throw new FireflyException(
                    'Database transaction failed: ' . $e->getMessage(),
                    0,
                    $e
                );
            } catch (\Exception $e) {
                Log::error('Transaction failed with exception: ' . $e->getMessage());
                throw new FireflyException(
                    'Transaction failed: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        // All attempts exhausted
        throw new FireflyException(
            sprintf('Transaction failed after %d attempts: %s', $attempts, $lastException?->getMessage()),
            0,
            $lastException
        );
    }

    /**
     * Check if an exception represents a database deadlock.
     */
    private function isDeadlock(\PDOException $e): bool
    {
        $message = strtolower($e->getMessage());
        $code = (string) $e->getCode();
        
        // MySQL deadlock codes
        if (in_array($code, ['40001', '1213'], true)) {
            return true;
        }
        
        // PostgreSQL deadlock codes
        if (in_array($code, ['40P01', '55P03'], true)) {
            return true;
        }
        
        // SQLite busy/locked
        if (str_contains($message, 'database is locked') || str_contains($message, 'database table is locked')) {
            return true;
        }
        
        // Check message for common deadlock indicators
        return str_contains($message, 'deadlock') || 
               str_contains($message, 'lock wait timeout') ||
               str_contains($message, 'concurrent update');
    }

    /**
     * Acquire a pessimistic lock on a model for update.
     *
     * @param string $modelClass The model class name
     * @param int $id The model ID
     * @return mixed The locked model instance
     * @throws FireflyException
     */
    protected function lockForUpdate(string $modelClass, int $id): mixed
    {
        $model = $modelClass::lockForUpdate()->find($id);
        
        if (null === $model) {
            throw new FireflyException(
                sprintf('Could not acquire lock on %s with ID %d', $modelClass, $id)
            );
        }
        
        return $model;
    }

    /**
     * Execute a callback with distributed locking using cache.
     *
     * @param string $lockKey Unique key for the lock
     * @param callable $callback The operation to execute
     * @param int $timeout Lock timeout in seconds
     * @return mixed The result of the callback
     * @throws FireflyException
     */
    protected function executeWithLock(string $lockKey, callable $callback, int $timeout = 10): mixed
    {
        $lock = \Cache::lock($lockKey, $timeout);
        
        if (!$lock->acquire()) {
            throw new FireflyException(
                sprintf('Could not acquire lock for key: %s', $lockKey)
            );
        }
        
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Create a savepoint for nested transactions.
     */
    protected function createSavepoint(string $name): void
    {
        DB::statement('SAVEPOINT ' . $name);
    }

    /**
     * Rollback to a savepoint.
     */
    protected function rollbackToSavepoint(string $name): void
    {
        DB::statement('ROLLBACK TO SAVEPOINT ' . $name);
    }

    /**
     * Release a savepoint.
     */
    protected function releaseSavepoint(string $name): void
    {
        DB::statement('RELEASE SAVEPOINT ' . $name);
    }
}
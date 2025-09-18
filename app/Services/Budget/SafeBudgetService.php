<?php

/**
 * SafeBudgetService.php
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

namespace FireflyIII\Services\Budget;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class SafeBudgetService
 * 
 * Provides safe budget operations with atomic checks and proper locking.
 */
class SafeBudgetService
{
    use TransactionServiceTrait;
    
    /**
     * Check if an amount can be spent from a budget atomically.
     *
     * @throws FireflyException
     */
    public function canSpend(
        Budget $budget,
        TransactionCurrency $currency,
        string $amount,
        Carbon $date
    ): bool {
        return $this->executeInTransaction(function () use ($budget, $currency, $amount, $date) {
            // Lock the budget for reading
            $budget = Budget::lockForUpdate()->findOrFail($budget->id);
            
            // Get applicable budget limit
            $limit = $this->getApplicableBudgetLimit($budget, $currency, $date);
            
            if (null === $limit) {
                // No limit means unlimited spending
                Log::debug(sprintf('No budget limit for budget #%d, allowing spend', $budget->id));
                return true;
            }
            
            // Lock the budget limit
            $limit = BudgetLimit::lockForUpdate()->findOrFail($limit->id);
            
            // Calculate spent amount
            $spent = $this->calculateSpentAmount($budget, $currency, $limit->start_date, $limit->end_date);
            
            // Calculate available amount
            $available = bcsub($limit->amount, $spent);
            
            // Check if amount can be spent
            $canSpend = bccomp($available, $amount) >= 0;
            
            Log::debug(sprintf(
                'Budget #%d: limit=%s, spent=%s, available=%s, requested=%s, can_spend=%s',
                $budget->id,
                $limit->amount,
                $spent,
                $available,
                $amount,
                $canSpend ? 'yes' : 'no'
            ));
            
            return $canSpend;
        });
    }
    
    /**
     * Update budget limit atomically.
     *
     * @throws FireflyException
     */
    public function updateBudgetLimit(
        Budget $budget,
        TransactionCurrency $currency,
        string $amount,
        Carbon $startDate,
        Carbon $endDate
    ): BudgetLimit {
        return $this->executeInTransaction(function () use ($budget, $currency, $amount, $startDate, $endDate) {
            // Validate amount
            if (bccomp($amount, '0') < 0) {
                throw new FireflyException('Budget limit amount cannot be negative');
            }
            
            // Lock the budget
            $budget = Budget::lockForUpdate()->findOrFail($budget->id);
            
            // Check for existing limit
            $existingLimit = BudgetLimit::lockForUpdate()
                ->where('budget_id', $budget->id)
                ->where('transaction_currency_id', $currency->id)
                ->where('start_date', $startDate->format('Y-m-d'))
                ->where('end_date', $endDate->format('Y-m-d'))
                ->first();
            
            if (null !== $existingLimit) {
                // Update existing limit
                $oldAmount = $existingLimit->amount;
                $existingLimit->amount = $amount;
                $existingLimit->save();
                
                Log::info(sprintf(
                    'Updated budget limit #%d from %s to %s',
                    $existingLimit->id,
                    $oldAmount,
                    $amount
                ));
                
                return $existingLimit;
            }
            
            // Create new limit
            $limit = new BudgetLimit();
            $limit->budget()->associate($budget);
            $limit->transactionCurrency()->associate($currency);
            $limit->start_date = $startDate;
            $limit->end_date = $endDate;
            $limit->amount = $amount;
            $limit->save();
            
            Log::info(sprintf(
                'Created new budget limit #%d for budget #%d with amount %s',
                $limit->id,
                $budget->id,
                $amount
            ));
            
            return $limit;
        });
    }
    
    /**
     * Check and update budget spending atomically.
     *
     * @throws FireflyException
     */
    public function recordSpending(
        Budget $budget,
        TransactionCurrency $currency,
        string $amount,
        Carbon $date
    ): void {
        $this->executeInTransaction(function () use ($budget, $currency, $amount, $date) {
            // Validate amount
            if (bccomp($amount, '0') <= 0) {
                throw new FireflyException('Spending amount must be positive');
            }
            
            // Lock the budget
            $budget = Budget::lockForUpdate()->findOrFail($budget->id);
            
            // Get applicable budget limit
            $limit = $this->getApplicableBudgetLimit($budget, $currency, $date);
            
            if (null === $limit) {
                // No limit, just log the spending
                Log::info(sprintf(
                    'Recording spending of %s %s for budget #%d (no limit)',
                    $currency->code,
                    $amount,
                    $budget->id
                ));
                return;
            }
            
            // Lock the budget limit
            $limit = BudgetLimit::lockForUpdate()->findOrFail($limit->id);
            
            // Calculate current spent amount
            $currentSpent = $this->calculateSpentAmount($budget, $currency, $limit->start_date, $limit->end_date);
            
            // Calculate new total
            $newTotal = bcadd($currentSpent, $amount);
            
            // Check if it exceeds the limit
            if (bccomp($newTotal, $limit->amount) > 0) {
                throw new FireflyException(sprintf(
                    'Spending %s %s would exceed budget limit of %s %s (current: %s)',
                    $currency->code,
                    $amount,
                    $currency->code,
                    $limit->amount,
                    $currentSpent
                ));
            }
            
            Log::info(sprintf(
                'Recorded spending of %s %s for budget #%d (new total: %s/%s)',
                $currency->code,
                $amount,
                $budget->id,
                $newTotal,
                $limit->amount
            ));
        });
    }
    
    /**
     * Get applicable budget limit for a date.
     */
    private function getApplicableBudgetLimit(
        Budget $budget,
        TransactionCurrency $currency,
        Carbon $date
    ): ?BudgetLimit {
        return BudgetLimit::where('budget_id', $budget->id)
            ->where('transaction_currency_id', $currency->id)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->orderBy('created_at', 'DESC')
            ->first();
    }
    
    /**
     * Calculate spent amount for a budget in a period.
     */
    private function calculateSpentAmount(
        Budget $budget,
        TransactionCurrency $currency,
        Carbon $startDate,
        Carbon $endDate
    ): string {
        $result = DB::table('transactions')
            ->join('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->join('budget_transaction_journal', 'transaction_journals.id', '=', 'budget_transaction_journal.transaction_journal_id')
            ->where('budget_transaction_journal.budget_id', $budget->id)
            ->where('transactions.transaction_currency_id', $currency->id)
            ->where('transaction_journals.date', '>=', $startDate->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $endDate->format('Y-m-d'))
            ->where('transactions.amount', '<', 0)
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->sum('transactions.amount');
        
        // Convert to positive amount and handle null
        $spent = (string) ($result ?? '0');
        $spent = bcmul($spent, '-1');
        
        return $spent;
    }
    
    /**
     * Transfer budget allocation between periods atomically.
     *
     * @throws FireflyException
     */
    public function transferBudgetAllocation(
        Budget $budget,
        TransactionCurrency $currency,
        string $amount,
        Carbon $fromPeriodStart,
        Carbon $fromPeriodEnd,
        Carbon $toPeriodStart,
        Carbon $toPeriodEnd
    ): void {
        $this->executeInTransaction(function () use (
            $budget,
            $currency,
            $amount,
            $fromPeriodStart,
            $fromPeriodEnd,
            $toPeriodStart,
            $toPeriodEnd
        ) {
            // Lock the budget
            $budget = Budget::lockForUpdate()->findOrFail($budget->id);
            
            // Get and lock source limit
            $sourceLimit = BudgetLimit::lockForUpdate()
                ->where('budget_id', $budget->id)
                ->where('transaction_currency_id', $currency->id)
                ->where('start_date', $fromPeriodStart->format('Y-m-d'))
                ->where('end_date', $fromPeriodEnd->format('Y-m-d'))
                ->first();
            
            if (null === $sourceLimit) {
                throw new FireflyException('Source budget limit not found');
            }
            
            // Check if amount can be transferred
            $sourceSpent = $this->calculateSpentAmount($budget, $currency, $fromPeriodStart, $fromPeriodEnd);
            $sourceAvailable = bcsub($sourceLimit->amount, $sourceSpent);
            
            if (bccomp($sourceAvailable, $amount) < 0) {
                throw new FireflyException(sprintf(
                    'Cannot transfer %s %s, only %s available',
                    $currency->code,
                    $amount,
                    $sourceAvailable
                ));
            }
            
            // Get or create destination limit
            $destLimit = BudgetLimit::lockForUpdate()
                ->where('budget_id', $budget->id)
                ->where('transaction_currency_id', $currency->id)
                ->where('start_date', $toPeriodStart->format('Y-m-d'))
                ->where('end_date', $toPeriodEnd->format('Y-m-d'))
                ->first();
            
            if (null === $destLimit) {
                $destLimit = new BudgetLimit();
                $destLimit->budget()->associate($budget);
                $destLimit->transactionCurrency()->associate($currency);
                $destLimit->start_date = $toPeriodStart;
                $destLimit->end_date = $toPeriodEnd;
                $destLimit->amount = '0';
            }
            
            // Update amounts
            $sourceLimit->amount = bcsub($sourceLimit->amount, $amount);
            $destLimit->amount = bcadd($destLimit->amount, $amount);
            
            $sourceLimit->save();
            $destLimit->save();
            
            Log::info(sprintf(
                'Transferred %s %s from budget limit #%d to #%d',
                $currency->code,
                $amount,
                $sourceLimit->id,
                $destLimit->id
            ));
        });
    }
}
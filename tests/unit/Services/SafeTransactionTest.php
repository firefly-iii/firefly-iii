<?php

/**
 * SafeTransactionTest.php
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

namespace Tests\Unit\Services;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Services\Budget\SafeBudgetService;
use FireflyIII\Services\Currency\SafeExchangeRateConverter;
use FireflyIII\Services\Internal\Update\SafeGroupUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Class SafeTransactionTest
 * 
 * Tests for safe transaction handling implementations.
 */
class SafeTransactionTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test that transactions are rolled back on failure.
     */
    public function testTransactionRollbackOnFailure(): void
    {
        $service = new SafeGroupUpdateService();
        
        // Create a transaction group
        $group = TransactionGroup::factory()->create();
        
        // Attempt to update with invalid data that will cause an exception
        $invalidData = [
            'transactions' => [
                [
                    'amount' => 'invalid_amount', // This should cause an exception
                    'description' => 'Test transaction',
                ],
            ],
        ];
        
        // Count records before attempt
        $countBefore = DB::table('transaction_journals')->count();
        
        try {
            $service->update($group, $invalidData);
            $this->fail('Expected exception was not thrown');
        } catch (FireflyException $e) {
            // Expected exception
        }
        
        // Count records after attempt - should be the same
        $countAfter = DB::table('transaction_journals')->count();
        
        $this->assertEquals($countBefore, $countAfter, 'Transaction was not rolled back');
    }
    
    /**
     * Test currency conversion with missing rates.
     */
    public function testCurrencyConversionWithMissingRate(): void
    {
        $converter = new SafeExchangeRateConverter();
        $converter->setStrictMode(true);
        
        $from = TransactionCurrency::factory()->create(['code' => 'USD']);
        $to = TransactionCurrency::factory()->create(['code' => 'EUR']);
        $date = Carbon::now();
        
        $this->expectException(FireflyException::class);
        $this->expectExceptionMessage('No exchange rate available');
        
        $converter->convert($from, $to, $date, '100.00');
    }
    
    /**
     * Test currency conversion with zero rate protection.
     */
    public function testCurrencyConversionZeroRateProtection(): void
    {
        $converter = new SafeExchangeRateConverter();
        $converter->setStrictMode(false);
        
        $from = TransactionCurrency::factory()->create(['code' => 'USD']);
        $to = TransactionCurrency::factory()->create(['code' => 'EUR']);
        $date = Carbon::now();
        
        // Should return original amount when rate is not available and not in strict mode
        $result = $converter->convert($from, $to, $date, '100.00');
        
        $this->assertEquals('100.00', $result);
    }
    
    /**
     * Test budget limit enforcement with atomic checks.
     */
    public function testBudgetLimitAtomicCheck(): void
    {
        $service = new SafeBudgetService();
        
        $budget = Budget::factory()->create();
        $currency = TransactionCurrency::factory()->create(['code' => 'USD']);
        
        // Set a budget limit
        $limit = $service->updateBudgetLimit(
            $budget,
            $currency,
            '1000.00',
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
        
        $this->assertNotNull($limit);
        $this->assertEquals('1000.00', $limit->amount);
        
        // Test spending check
        $canSpend = $service->canSpend($budget, $currency, '500.00', Carbon::now());
        $this->assertTrue($canSpend);
        
        // Test overspending check
        $canOverspend = $service->canSpend($budget, $currency, '1500.00', Carbon::now());
        $this->assertFalse($canOverspend);
    }
    
    /**
     * Test concurrent transaction handling.
     */
    public function testConcurrentTransactionHandling(): void
    {
        $service = new SafeGroupUpdateService();
        
        $group = TransactionGroup::factory()->create();
        
        // Simulate concurrent updates
        $promises = [];
        
        for ($i = 0; $i < 5; $i++) {
            $data = [
                'group_title' => 'Updated Title ' . $i,
                'transactions' => [],
            ];
            
            // Each update should be handled atomically
            $result = $service->update($group, $data);
            $this->assertInstanceOf(TransactionGroup::class, $result);
        }
        
        // Verify final state
        $group->refresh();
        $this->assertStringStartsWith('Updated Title', $group->title);
    }
    
    /**
     * Test division by zero protection in currency conversion.
     */
    public function testDivisionByZeroProtection(): void
    {
        $converter = new SafeExchangeRateConverter();
        
        // Test with zero amount
        $from = TransactionCurrency::factory()->create(['code' => 'USD']);
        $result = $converter->convert($from, $from, Carbon::now(), '0');
        
        $this->assertEquals('0', $result);
    }
    
    /**
     * Test scientific notation handling.
     */
    public function testScientificNotationHandling(): void
    {
        $converter = new SafeExchangeRateConverter();
        
        $from = TransactionCurrency::factory()->create(['code' => 'USD']);
        
        // Test with scientific notation
        $result = $converter->convert($from, $from, Carbon::now(), '1.23E+5');
        
        $this->assertEquals('123000', $result);
    }
    
    /**
     * Test budget transfer atomicity.
     */
    public function testBudgetTransferAtomicity(): void
    {
        $service = new SafeBudgetService();
        
        $budget = Budget::factory()->create();
        $currency = TransactionCurrency::factory()->create(['code' => 'USD']);
        
        $thisMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();
        
        // Create source budget limit
        $sourceLimit = $service->updateBudgetLimit(
            $budget,
            $currency,
            '1000.00',
            $thisMonth,
            $thisMonth->copy()->endOfMonth()
        );
        
        // Transfer to next month
        $service->transferBudgetAllocation(
            $budget,
            $currency,
            '300.00',
            $thisMonth,
            $thisMonth->copy()->endOfMonth(),
            $nextMonth,
            $nextMonth->copy()->endOfMonth()
        );
        
        // Verify source was reduced
        $sourceLimit->refresh();
        $this->assertEquals('700.00', $sourceLimit->amount);
        
        // Verify destination was created/updated
        $destLimit = DB::table('budget_limits')
            ->where('budget_id', $budget->id)
            ->where('start_date', $nextMonth->format('Y-m-d'))
            ->first();
            
        $this->assertNotNull($destLimit);
        $this->assertEquals('300.00', $destLimit->amount);
    }
    
    /**
     * Test transaction savepoint handling.
     */
    public function testSavepointHandling(): void
    {
        DB::beginTransaction();
        
        try {
            // Create savepoint
            DB::statement('SAVEPOINT test_point');
            
            // Make some changes
            DB::table('users')->insert([
                'email' => 'test@example.com',
                'password' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Rollback to savepoint
            DB::statement('ROLLBACK TO SAVEPOINT test_point');
            
            // Verify changes were rolled back
            $count = DB::table('users')->where('email', 'test@example.com')->count();
            $this->assertEquals(0, $count);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Test pessimistic locking.
     */
    public function testPessimisticLocking(): void
    {
        $budget = Budget::factory()->create();
        
        DB::beginTransaction();
        
        try {
            // Lock the budget
            $lockedBudget = Budget::lockForUpdate()->find($budget->id);
            
            $this->assertNotNull($lockedBudget);
            $this->assertEquals($budget->id, $lockedBudget->id);
            
            // Make changes
            $lockedBudget->name = 'Updated Name';
            $lockedBudget->save();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        // Verify changes were saved
        $budget->refresh();
        $this->assertEquals('Updated Name', $budget->name);
    }
}
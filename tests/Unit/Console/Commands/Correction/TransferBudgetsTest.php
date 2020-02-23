<?php
/**
 * TransferBudgetsTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Unit\Console\Commands\Correction;


use Log;
use Tests\TestCase;

/**
 * Class TransferBudgetsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransferBudgetsTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\TransferBudgets
     */
    public function testHandle(): void
    {
        $this->artisan('firefly-iii:fix-transfer-budgets')
             ->expectsOutput('No invalid budget/journal entries.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\TransferBudgets
     */
    public function testHandleBudget(): void
    {
        $deposit = $this->getRandomDeposit();
        $budget  = $this->user()->budgets()->inRandomOrder()->first();

        $deposit->budgets()->save($budget);

        $this->artisan('firefly-iii:fix-transfer-budgets')
             ->expectsOutput(sprintf('Transaction journal #%d is a %s, so has no longer a budget.', $deposit->id, $deposit->transactionType->type))
             ->expectsOutput('Corrected 1 invalid budget/journal entries (entry).')
             ->assertExitCode(0);

        // verify change
        $this->assertCount(0, $deposit->budgets()->get());
    }

}

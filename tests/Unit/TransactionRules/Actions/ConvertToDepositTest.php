<?php
/**
 * ConvertToDepositTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\TransactionRules\Actions;


use Exception;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\ConvertToDeposit;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToDepositTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConvertToDepositTest extends TestCase
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
     * Convert a transfer to a deposit.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToDeposit
     */
    public function testActTransfer(): void
    {
        $revenue = $this->getRandomRevenue();
        $name    = 'Random revenue #' . $this->randomInt();
        $journal = $this->getRandomTransfer();

        // journal is a transfer:
        $this->assertEquals(TransactionType::TRANSFER, $journal->transactionType->type);

        // mock used stuff:
        $factory = $this->mock(AccountFactory::class);
        $factory->shouldReceive('setUser')->once();
        $factory->shouldReceive('findOrCreate')->once()->withArgs([$name, AccountType::REVENUE])->andReturn($revenue);


        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToDeposit($ruleAction);
        try {
            $result = $action->act($journal);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a deposit.
        $journal->refresh();
        $this->assertEquals(TransactionType::DEPOSIT, $journal->transactionType->type);
    }

    /**
     * Convert a withdrawal to a deposit.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToDeposit
     */
    public function testActWithdrawal(): void
    {
        $revenue = $this->getRandomRevenue();
        $name    = 'Random revenue #' . $this->randomInt();
        $journal = $this->getRandomWithdrawal();

        // journal is a withdrawal:
        $this->assertEquals(TransactionType::WITHDRAWAL, $journal->transactionType->type);

        // mock used stuff:
        $factory = $this->mock(AccountFactory::class);
        $factory->shouldReceive('setUser')->once();
        $factory->shouldReceive('findOrCreate')->once()->withArgs([$name, AccountType::REVENUE])->andReturn($revenue);


        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $name;
        $action                   = new ConvertToDeposit($ruleAction);
        try {
            $result = $action->act($journal);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a deposit.
        $journal->refresh();
        $this->assertEquals(TransactionType::DEPOSIT, $journal->transactionType->type);
    }


}

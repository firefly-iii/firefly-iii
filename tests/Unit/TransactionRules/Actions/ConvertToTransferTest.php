<?php
/**
 * ConvertToTransferTest.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Actions\ConvertToTransfer;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToTransferTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConvertToTransferTest extends TestCase
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
     * Convert a deposit to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActDeposit(): void
    {
        $deposit = $this->getRandomDeposit();

        // make sure that $asset is not the destination account of $deposit:
        $forbiddenId = (int)$deposit->transactions()->where('amount', '>', 0)->first()->account_id;
        $asset       = $this->getRandomAsset($forbiddenId);

        // mock used stuff:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->withArgs(
            [$asset->name,
             [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]]
        )->andReturn($asset);

        // fire the action:
        $rule                     = new Rule;
        $rule->title              = 'OK';
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $asset->name;
        $ruleAction->rule         = $rule;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->act($deposit);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $deposit->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $deposit->transactionType->type);
    }

    /**
     * Convert a withdrawal to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActWithdrawal(): void
    {
        $withdrawal = $this->getRandomWithdrawal();

        // make sure that $asset is not the source account of $withdrawal:
        $forbiddenId = (int)$withdrawal->transactions()->where('amount', '<', 0)->first()->account_id;
        $asset       = $this->getRandomAsset($forbiddenId);

        // mock used stuff:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->withArgs([$asset->name, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]])->andReturn($asset);

        // fire the action:
        $rule                     = new Rule;
        $rule->title              = 'OK';
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $asset->name;
        $ruleAction->rule         = $rule;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->act($withdrawal);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $withdrawal->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $withdrawal->transactionType->type);
    }


}

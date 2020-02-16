<?php
/**
 * SetDestinationAccountTest.php
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

namespace Tests\Unit\TransactionRules\Actions;

use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Actions\SetDestinationAccount;
use Tests\TestCase;

/**
 * Try split journal
 *
 * Class SetDestinationAccountTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetDestinationAccountTest extends TestCase
{
    /**
     * Give deposit existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActDepositExisting(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $deposit       = $this->getRandomDeposit();
        $destinationTr = $deposit->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $deposit->user;
        $accountType   = AccountType::whereType(AccountType::ASSET)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($deposit);
        $this->assertTrue($result);

        // test journal for new account
        $destinationTr  = $deposit->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

    /**
     * Give deposit not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActDepositNotExisting(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $deposit      = $this->getRandomDeposit();

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Not existing asset account #' . $this->randomInt();
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($deposit);
        $this->assertFalse($result);
    }

    /**
     * Give withdrawal not existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithDrawalNotExisting(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->getRandomExpense();
        $withdrawal   = $this->getRandomWithdrawal();

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);
        $accountRepos->shouldReceive('store')->once()->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Not existing expense account #' . $this->randomInt();
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($withdrawal);

        $this->assertTrue($result);
    }

    /**
     * Give withdrawal existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithdrawalExisting(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $withdrawal    = $this->getRandomWithdrawal();
        $destinationTr = $withdrawal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $withdrawal->user;
        $accountType   = AccountType::whereType(AccountType::EXPENSE)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($withdrawal);
        $this->assertTrue($result);

        // test journal for new account
        $destinationTr  = $withdrawal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

}

<?php
/**
 * SetSourceAccountTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Actions;

use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Actions\SetSourceAccount;
use Tests\TestCase;

/**
 * Class SetSourceAccountTest
 */
class SetSourceAccountTest extends TestCase
{
    /**
     * Give deposit existing revenue account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActDepositExistingUpdated(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $deposit      = $this->getRandomDeposit();
        $sourceTr     = $deposit->transactions()->where('amount', '<', 0)->first();
        $source       = $sourceTr->account;
        $user         = $deposit->user;
        $accountType  = AccountType::whereType(AccountType::REVENUE)->first();
        $account      = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($deposit);
        $this->assertTrue($result);

        // test journal for new account
        $sourceTr  = $deposit->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);
    }

    /**
     * Give deposit new revenueaccount.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActDepositRevenue(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->getRandomRevenue();
        $deposit      = $this->getRandomDeposit();

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);
        $accountRepos->shouldReceive('store')->once()->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new revenue #' . $this->randomInt();
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($deposit);
        $this->assertTrue($result);
    }

    /**
     * Give withdrawal existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActWithdrawalExistingUpdated(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $withdrawal   = $this->getRandomWithdrawal();

        $sourceTr    = $withdrawal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $withdrawal->user;
        $accountType = AccountType::whereType(AccountType::ASSET)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);


        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($withdrawal);
        $this->assertTrue($result);

        // test journal for new account
        $sourceTr  = $withdrawal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);
    }

    /**
     * Give withdrawal not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActWithdrawalNotExisting(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $withdrawal   = $this->getRandomWithdrawal();

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new account #' . $this->randomInt();
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($withdrawal);
        $this->assertFalse($result);
    }
}

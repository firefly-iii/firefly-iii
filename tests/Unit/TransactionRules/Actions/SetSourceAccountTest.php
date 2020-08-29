<?php
/**
 * SetSourceAccountTest.php
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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
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
        $deposit     = $this->getRandomDeposit();
        $sourceTr    = $deposit->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $deposit->user;
        $accountType = AccountType::whereType(AccountType::REVENUE)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        $array = [
            'user_id'               => $this->user()->id,
            'transaction_journal_id' => $deposit->id,
            'transaction_type_type' => TransactionType::DEPOSIT,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // test journal for new account
        $sourceTr  = $deposit->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);

        $sourceTr->account_id = $source->id;
        $sourceTr->save();
    }

    /**
     * Give deposit new revenueaccount.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActDepositRevenue(): void
    {
        $deposit = $this->getRandomDeposit();

        $array = [
            'transaction_journal_id' => $deposit->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type' => TransactionType::DEPOSIT,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = sprintf('Some new revenue #%d', $this->randomInt());
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);
    }

    /**
     * Give withdrawal existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActWithdrawalExistingUpdated(): void
    {
        $withdrawal  = $this->getRandomWithdrawal();
        $sourceTr    = $withdrawal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $withdrawal->user;
        $accountType = AccountType::whereType(AccountType::ASSET)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        $array = [
            'user_id' => $this->user()->id,
            'transaction_type_type' => TransactionType::WITHDRAWAL,
            'transaction_journal_id' => $withdrawal->id,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // test journal for new account
        $sourceTr  = $withdrawal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);

        $sourceTr->account_id = $source->id;
        $sourceTr->save();
    }

    /**
     * Give withdrawal not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActWithdrawalNotExisting(): void
    {
        $withdrawal = $this->getRandomWithdrawal();

        $array = [
            'user_id' => $this->user()->id,
            'transaction_type_type' => TransactionType::WITHDRAWAL,
            'transaction_journal_id' => $withdrawal->id,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = sprintf('Some new account #%d', $this->randomInt());
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertFalse($result);
    }
}

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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\SetDestinationAccount;
use Tests\TestCase;

/**
 * Try split journal
 *
 * Class SetDestinationAccountTest
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
        // get random deposit:
        $deposit        = $this->getRandomDeposit();
        $destinationTr  = $deposit->transactions()->where('amount', '>', 0)->first();
        $destination    = $destinationTr->account;
        $oldDestination = $destinationTr->account_id;

        // grab unused asset account:
        $user           = $deposit->user;
        $accountType    = AccountType::whereType(AccountType::ASSET)->first();
        $newDestination = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $newDestination->id);

        // array with info:
        $array = [
            'transaction_journal_id' => $deposit->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => TransactionType::DEPOSIT,
            'source_account_type'    => AccountType::REVENUE,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newDestination->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // test journal for new account
        $destinationTr->refresh();
        $updatedDestination = $destinationTr->account;
        $this->assertEquals($newDestination->id, $updatedDestination->id);
        $this->assertEquals($destinationTr->account_id, $newDestination->id);

        $destinationTr->account_id = $oldDestination;
        $destinationTr->save();
    }

    /**
     * Give deposit not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActDepositNotExisting(): void
    {
        // get random deposit:
        $deposit        = $this->getRandomDeposit();
        $destinationTr  = $deposit->transactions()->where('amount', '>', 0)->first();
        $oldDestination = $destinationTr->account;

        // array with info:
        $array = [
            'transaction_journal_id' => $deposit->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => TransactionType::DEPOSIT,
            'source_account_type'    => AccountType::REVENUE,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = sprintf('Not existing asset account #%d', $this->randomInt());
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertFalse($result);

        // test journal for new account
        $destinationTr->refresh();
        $this->assertEquals($destinationTr->account_id, $oldDestination->id);
    }

    /**
     * Give withdrawal not existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithDrawalNotExisting(): void
    {
        // get random withdrawal:
        $withdrawal     = $this->getRandomWithdrawal();
        $destinationTr  = $withdrawal->transactions()->where('amount', '>', 0)->first();
        $oldDestination = $destinationTr->account;
        // array with info:
        $array = [
            'transaction_journal_id' => $withdrawal->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => TransactionType::WITHDRAWAL,
            'source_account_type'    => AccountType::ASSET,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = sprintf('Not existing expense account #%d', $this->randomInt());
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // test journal for new account
        $destinationTr->refresh();
        $newDestination = $destinationTr->account;
        $this->assertEquals($newDestination->name, $ruleAction->action_value);

        $destinationTr->account_id = $oldDestination;
        $destinationTr->save();
    }

    /**
     * Give withdrawal existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithdrawalExisting(): void
    {
        // get random withdrawal:
        $withdrawal     = $this->getRandomWithdrawal();
        $destinationTr  = $withdrawal->transactions()->where('amount', '>', 0)->first();
        $oldDestination = $destinationTr->account;
        $newDestination = $this->user()->accounts()->where('id', '!=', $oldDestination->id)->first();

        // array with info:
        $array = [
            'transaction_journal_id' => $withdrawal->id,
            'user_id'                => $this->user()->id,
            'transaction_type_type'  => TransactionType::WITHDRAWAL,
            'source_account_type'    => AccountType::ASSET,
        ];

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newDestination->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // test journal for new account
        $destinationTr->refresh();
        $newDestination = $destinationTr->account;
        $this->assertEquals($newDestination->name, $ruleAction->action_value);
    }

}

<?php
/**
 * SetDestinationAccountTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Actions;


use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\SetDestinationAccount;
use Tests\TestCase;

/**
 * Try split journal
 *
 * Class SetDestinationAccountTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class SetDestinationAccountTest extends TestCase
{
    /**
     * Give deposit existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::findAssetAccount()
     */
    public function testActDepositExisting()
    {
        $type          = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal       = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $journal->user;
        $accountType   = AccountType::whereType(AccountType::ASSET)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

    /**
     * Give deposit new asset account (will fail)
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::findAssetAccount()
     */
    public function testActDepositNew()
    {
        $type          = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal       = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new asset ' . rand(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);

        // test journal for still having old account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertEquals($destination->id, $newDestination->id);
    }

    /**
     * Give withdrawal existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::findExpenseAccount
     */
    public function testActWithdrawalExisting()
    {
        $type          = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal       = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $journal->user;
        $accountType   = AccountType::whereType(AccountType::EXPENSE)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

    /**
     * Give withdrawal new expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::findExpenseAccount
     */
    public function testActWithdrawalNew()
    {
        $type          = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal       = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new expense ' . rand(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
    }

    /**
     * Test this on a split journal.
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount::act()
     */
    public function testSplitJournal()
    {
        $transaction = Transaction::orderBy('count', 'DESC')->groupBy('transaction_journal_id')
                                  ->get(['transaction_journal_id', DB::raw('COUNT(transaction_journal_id) as count')])
                                  ->first();
        $journal     = TransactionJournal::find($transaction->transaction_journal_id);
        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new asset ' . rand(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }

}

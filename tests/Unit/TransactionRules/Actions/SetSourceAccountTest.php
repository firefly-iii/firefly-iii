<?php
/**
 * SetSourceAccountTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Actions;

use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\TransactionRules\Actions\SetSourceAccount;
use Tests\TestCase;

/**
 * Class SetSourceAccountTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class SetSourceAccountTest extends TestCase
{
    /**
     * Give deposit existing revenue account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findRevenueAccount()
     */
    public function testActDepositExistingUpdated()
    {
        $type        = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal     = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $sourceTr    = $journal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $journal->user;
        $accountType = AccountType::whereType(AccountType::REVENUE)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal   = TransactionJournal::find($journal->id);
        $sourceTr  = $journal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);
    }

    /**
     * Give deposit new revenue account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findRevenueAccount
     */
    public function testActDepositNewUpdated()
    {
        $type     = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal  = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $sourceTr = $journal->transactions()->where('amount', '<', 0)->first();
        $source   = $sourceTr->account;

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new revenue ' . rand(1, 1000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal   = TransactionJournal::find($journal->id);
        $sourceTr  = $journal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
    }

    /**
     * Give withdrawal existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findAssetAccount()
     */
    public function testActWithdrawalExistingUpdated()
    {
        $type        = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal     = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $sourceTr    = $journal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $journal->user;
        $accountType = AccountType::whereType(AccountType::ASSET)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal   = TransactionJournal::find($journal->id);
        $sourceTr  = $journal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertNotEquals($source->id, $newSource->id);
        $this->assertEquals($newSource->id, $account->id);
    }

    /**
     * Give withdrawal new asset account (will fail)
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findAssetAccount()
     */
    public function testActWithdrawalNew()
    {
        $type     = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal  = TransactionJournal::where('transaction_type_id', $type->id)->first();
        $sourceTr = $journal->transactions()->where('amount', '<', 0)->first();
        $source   = $sourceTr->account;

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new asset ' . rand(1, 1000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);

        // test journal for still having old account
        $journal   = TransactionJournal::find($journal->id);
        $sourceTr  = $journal->transactions()->where('amount', '<', 0)->first();
        $newSource = $sourceTr->account;
        $this->assertEquals($source->id, $newSource->id);
    }

    /**
     * Test this on a split journal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
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
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }

}
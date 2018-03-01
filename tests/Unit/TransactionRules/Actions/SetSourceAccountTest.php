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
use FireflyIII\Models\TransactionType;
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
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findRevenueAccount()
     */
    public function testActDepositExistingUpdated()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);


        $type        = TransactionType::whereType(TransactionType::DEPOSIT)->first();

        // select split transactions to exclude them later:
        $set = TransactionJournal::where('transaction_type_id', $type->id)->get(['transaction_journals.*']);
        foreach ($set as $current) {
            if ($current->transactions()->count() === 2) {
                $journal = $current;
                break;
            }
        }

        $sourceTr    = $journal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $journal->user;
        $accountType = AccountType::whereType(AccountType::REVENUE)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

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
     * Give withdrawal existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::findAssetAccount()
     */
    public function testActWithdrawalExistingUpdated()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();

        // select split transactions to exclude them later:
        $set = TransactionJournal::where('transaction_type_id', $type->id)->get(['transaction_journals.*']);
        foreach ($set as $current) {
            if ($current->transactions()->count() === 2) {
                $journal = $current;
                break;
            }
        }

        $sourceTr     = $journal->transactions()->where('amount', '<', 0)->first();
        $source       = $sourceTr->account;
        $user         = $journal->user;
        $accountType  = AccountType::whereType(AccountType::ASSET)->first();
        $account      = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
        $this->assertNotEquals($source->id, $account->id);


        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

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
     * Test this on a split journal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount::act()
     */
    public function testSplitJournal()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transaction  = Transaction::orderBy('count', 'DESC')->groupBy('transaction_journal_id')
                                   ->get(['transaction_journal_id', DB::raw('COUNT(transaction_journal_id) as count')])
                                   ->first();
        $journal      = TransactionJournal::find($transaction->transaction_journal_id);

        // mock
        $accountRepos->shouldReceive('setUser');
        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new asset ' . rand(1, 1000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }
}

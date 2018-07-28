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
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActDepositExistingUpdated(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);


        $type = TransactionType::whereType(TransactionType::DEPOSIT)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

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
     * Give deposit new revenueaccount.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActDepositRevenue(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $account      = $this->user()->accounts()->inRandomOrder()->where('account_type_id', 5)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);
        $accountRepos->shouldReceive('store')->once()->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new revenue #' . random_int(1, 10000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
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
        $type         = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        $sourceTr    = $journal->transactions()->where('amount', '<', 0)->first();
        $source      = $sourceTr->account;
        $user        = $journal->user;
        $accountType = AccountType::whereType(AccountType::ASSET)->first();
        $account     = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $source->id)->first();
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
     * Give withdrawal not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testActWithdrawalNotExisting(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new account #' . random_int(1, 10000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }

    /**
     * Test this on a split journal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetSourceAccount
     */
    public function testSplitJournal(): void
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
        $ruleAction->action_value = 'Some new asset ' . random_int(1, 10000);
        $action                   = new SetSourceAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }
}

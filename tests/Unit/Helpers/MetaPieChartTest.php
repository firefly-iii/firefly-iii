<?php
/**
 * MetaPieChartTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\Helpers;


use Carbon\Carbon;
use FireflyIII\Helpers\Chart\MetaPieChart;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MetaPieChartTest extends TestCase
{
    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::__construct
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::generate
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::getTransactions
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::groupByFields
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::organizeByType
     */
    public function testGenerateIncomeAccount()
    {
        $som        = (new Carbon())->startOfMonth();
        $eom        = (new Carbon())->endOfMonth();
        $collection = $this->fakeTransactions();
        $accounts   = [
            1 => factory(Account::class)->make(),
            2 => factory(Account::class)->make(),
        ];

        // mock collector so the correct set of journals is returned:
        // then verify the results.
        $collector = $this->mock(JournalCollectorInterface::class);

        $collector->shouldReceive('addFilter')->withArgs([NegativeAmountFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->withArgs([OpposingAccountFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('removeFilter')->withArgs([TransferFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('setUser')->andReturnSelf()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($collection);

        // mock all repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($accounts[1]);
        $accountRepos->shouldReceive('find')->withArgs([2])->andReturn($accounts[2]);


        $helper = new MetaPieChart();
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $chart = $helper->generate('income', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertEquals($keys[0], $accounts[1]->name);
        $this->assertEquals($keys[1], $accounts[2]->name);
        $this->assertTrue(bccomp('1000', $chart[$accounts[1]->name]) === 0);
        $this->assertTrue(bccomp('1000', $chart[$accounts[2]->name]) === 0);

        $this->assertTrue(true);


    }

    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::__construct
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::generate
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::getTransactions
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::groupByFields
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart::organizeByType
     */
    public function testGenerateIncomeAccountWithOthers()
    {
        $som        = (new Carbon())->startOfMonth();
        $eom        = (new Carbon())->endOfMonth();
        $collection = $this->fakeTransactions();
        $others     = $this->fakeOthers();
        $accounts   = [
            1 => factory(Account::class)->make(),
            2 => factory(Account::class)->make(),
        ];

        // mock collector so the correct set of journals is returned:
        // then verify the results.
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('addFilter')->withArgs([NegativeAmountFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->withArgs([OpposingAccountFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('removeFilter')->withArgs([TransferFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('setUser')->andReturnSelf()->twice();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->twice();
        $collector->shouldReceive('setRange')->andReturnSelf()->twice();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($collection)->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($others)->once();

        // mock all repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($accounts[1]);
        $accountRepos->shouldReceive('find')->withArgs([2])->andReturn($accounts[2]);


        $helper = new MetaPieChart();
        $helper->setCollectOtherObjects(true);
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $chart = $helper->generate('income', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertEquals($keys[0], $accounts[1]->name);
        $this->assertEquals($keys[1], $accounts[2]->name);
        $this->assertTrue(bccomp('1000', $chart[$accounts[1]->name]) === 0);
        $this->assertTrue(bccomp('1000', $chart[$accounts[2]->name]) === 0);
        $this->assertTrue(bccomp('1000', $chart['Everything else']) === 0);

        $this->assertTrue(true);


    }

    private function fakeOthers(): Collection
    {
        $set = new Collection;

        for ($i = 0; $i < 30; $i++) {
            $transaction = new Transaction;

            // basic fields.
            $transaction->opposing_account_id             = 3;
            $transaction->transaction_journal_budget_id   = 3;
            $transaction->transaction_budget_id           = 3;
            $transaction->transaction_journal_category_id = 3;
            $transaction->transaction_category_id         = 3;
            $transaction->transaction_amount              = '100';
            $set->push($transaction);
        }

        return $set;
    }

    private function fakeTransactions(): Collection
    {
        $set = new Collection;
        for ($i = 0; $i < 10; $i++) {
            $transaction = new Transaction;

            // basic fields.
            $transaction->opposing_account_id             = 1;
            $transaction->transaction_journal_budget_id   = 1;
            $transaction->transaction_budget_id           = 1;
            $transaction->transaction_journal_category_id = 1;
            $transaction->transaction_category_id         = 1;
            $transaction->transaction_amount              = '100';
            $set->push($transaction);
        }

        for ($i = 0; $i < 10; $i++) {
            $transaction = new Transaction;

            // basic fields.
            $transaction->opposing_account_id             = 2;
            $transaction->transaction_journal_budget_id   = 2;
            $transaction->transaction_budget_id           = 2;
            $transaction->transaction_journal_category_id = 2;
            $transaction->transaction_category_id         = 2;
            $transaction->transaction_amount              = '100';
            $set->push($transaction);
        }

        return $set;
    }

}

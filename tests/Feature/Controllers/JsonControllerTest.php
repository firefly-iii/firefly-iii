<?php
/**
 * JsonControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Amount;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class JsonControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::action
     * @covers \FireflyIII\Http\Controllers\JsonController::__construct
     */
    public function testAction()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('json.action'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxBillsPaid
     */
    public function testBoxBillsPaid()
    {
        // mock stuff

        $billRepos    = $this->mock(BillRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $billRepos->shouldReceive('getBillsPaidInRange')->andReturn('-100');

        $this->be($this->user());
        $currency = Amount::getDefaultCurrency();
        $response = $this->get(route('json.box.paid'));
        $response->assertStatus(200);
        $response->assertExactJson(['amount' => Amount::formatAnything($currency, '100', false), 'amount_raw' => '100', 'box' => 'bills-paid']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxBillsUnpaid
     */
    public function testBoxBillsUnpaid()
    {
        // mock stuff

        $billRepos    = $this->mock(BillRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $billRepos->shouldReceive('getBillsUnpaidInRange')->andReturn('100');


        $this->be($this->user());
        $currency = Amount::getDefaultCurrency();
        $response = $this->get(route('json.box.unpaid'));
        $response->assertStatus(200);
        $response->assertExactJson(['amount' => Amount::formatAnything($currency, '100', false), 'amount_raw' => '100', 'box' => 'bills-unpaid']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxIn
     */
    public function testBoxIn()
    {
        // mock stuff

        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $transaction                     = factory(Transaction::class)->make();
        $transaction->transaction_amount = '100.00';

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]))->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();


        $this->be($this->user());
        $currency = Amount::getDefaultCurrency();
        $response = $this->get(route('json.box.in'));
        $response->assertStatus(200);
        $response->assertExactJson(['amount' => Amount::formatAnything($currency, '100', false), 'amount_raw' => '100', 'box' => 'in']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxOut
     */
    public function testBoxOut()
    {
        // mock stuff

        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $transaction                     = factory(Transaction::class)->make();
        $transaction->transaction_amount = '100.00';

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]))->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();

        $this->be($this->user());
        $currency = Amount::getDefaultCurrency();
        $response = $this->get(route('json.box.out'));
        $response->assertStatus(200);
        $response->assertExactJson(['amount' => Amount::formatAnything($currency, '100', false), 'amount_raw' => '100', 'box' => 'out']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::budgets
     */
    public function testBudgets()
    {
        // mock stuff
        $budget        = factory(Budget::class)->make();
        $categoryRepos = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));
        $this->be($this->user());
        $response = $this->get(route('json.budgets'));
        $response->assertStatus(200);
        $response->assertExactJson([$budget->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::categories
     */
    public function testCategories()
    {
        // mock stuff
        $category      = factory(Category::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $this->be($this->user());
        $response = $this->get(route('json.categories'));
        $response->assertStatus(200);
        $response->assertExactJson([$category->name]);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::tags
     */
    public function testTags()
    {
        // mock stuff
        $tag          = factory(Tag::class)->make();
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]))->once();

        $this->be($this->user());
        $response = $this->get(route('json.tags'));
        $response->assertStatus(200);
        $response->assertExactJson([$tag->tag]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::transactionTypes
     */
    public function testTransactionTypes()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getTransactionTypes')->once()->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.transaction-types', ['deposit']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::trigger
     */
    public function testTrigger()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('json.trigger'));
        $response->assertStatus(200);
    }

}

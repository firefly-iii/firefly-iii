<?php
/**
 * BulkControllerTest.php
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

namespace Tests\Feature\Controllers\Transaction;

use Amount;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class BulkControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BulkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     */
    public function testEditWithdrawal(): void
    {
        // mock stuff:
        $journalRepos    = $this->mockDefaultSession();
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $collector       = $this->mock(GroupCollectorInterface::class);
        $withdrawal      = $this->getRandomWithdrawal();
        $withdrawalArray = $this->getRandomWithdrawalAsArray();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $collector->shouldReceive('setTypes')
                  ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]])->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withTagInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournalIds')->atLeast()->once()->withArgs([[$withdrawal->id]])->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$withdrawalArray]);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalSourceAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('getJournalDestinationAccounts')->andReturn(new Collection);
        $journalRepos->shouldReceive('getTransactionType')->andReturn('Transfer');
        $journalRepos->shouldReceive('isJournalReconciled')->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('transactions.bulk.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Bulk edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Requests\BulkEditJournalRequest
     */
    public function testUpdate(): void
    {
        $tags       = ['a', 'b', 'c'];
        $budget     = $this->getRandomBudget();
        $category   = $this->getRandomCategory();
        $withdrawal = $this->getRandomWithdrawalAsArray();
        $data       = [
            'category'  => $category->name,
            'budget_id' => $budget->id,
            'tags'      => 'a,b,c',
            'journals'  => [$withdrawal['transaction_journal_id']],
        ];

        $repository = $this->mockDefaultSession();

        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('updateBudget')->atLeast()->once()->andReturn(new TransactionJournal())->withArgs([Mockery::any(), $data['budget_id']]);
        $repository->shouldReceive('updateCategory')->atLeast()->once()->andReturn(new TransactionJournal())->withArgs([Mockery::any(), $data['category']]);
        $repository->shouldReceive('updateTags')->atLeast()->once()->andReturn(new TransactionJournal())->withArgs([Mockery::any(), $tags]);

        $repository->shouldReceive('findNull')->atLeast()->once()->andReturn(new TransactionJournal);


        $route = route('transactions.bulk.update');
        $this->be($this->user());
        $response = $this->post($route, $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\BulkController
     * @covers \FireflyIII\Http\Requests\BulkEditJournalRequest
     */
    public function testUpdateIgnoreAll(): void
    {
        $budget     = $this->getRandomBudget();
        $category   = $this->getRandomCategory();
        $withdrawal = $this->getRandomWithdrawalAsArray();
        $data       = [
            'category'        => $category->name,
            'budget_id'       => $budget->id,
            'tags'            => 'a,b,c',
            'journals'        => [$withdrawal['transaction_journal_id']],
            'ignore_category' => '1',
            'ignore_budget'   => '1',
            'ignore_tags'     => '1',
        ];

        $repository = $this->mockDefaultSession();

        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldNotReceive('updateBudget');
        $repository->shouldNotReceive('updateCategory');
        $repository->shouldNotReceive('updateTags');
        $repository->shouldReceive('findNull')->atLeast()->once()->andReturn(new TransactionJournal);


        $route = route('transactions.bulk.update');
        $this->be($this->user());
        $response = $this->post($route, $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}

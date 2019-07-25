<?php
/**
 * MassControllerTest.php
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
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class MassControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testDelete(): void
    {

        $this->mockDefaultSession();
        $withdrawal      = $this->getRandomWithdrawal();
        $withdrawalArray = $this->getRandomWithdrawalAsArray();
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $collector = $this->mock(GroupCollectorInterface::class);
        $collector->shouldReceive('setTypes')
                  ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]])->atLeast()->once()->andReturnSelf();

        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withTagInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournalIds')->withArgs([[$withdrawal->id]])->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$withdrawalArray]);

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');

        $this->be($this->user());
        $response = $this->get(route('transactions.mass.delete', [$withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Delete a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testDestroy(): void
    {
        $repository = $this->mockDefaultSession();
        $deposit    = $this->getRandomDeposit();

        $repository->shouldReceive('findNull')->atLeast()->once()->andReturn($deposit);
        $repository->shouldReceive('destroyJournal')->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['transactions.mass-delete.uri' => 'http://localhost']);

        $data = ['confirm_mass_delete' => [$deposit->id],];
        $this->be($this->user());
        $response = $this->post(route('transactions.mass.destroy'), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testEdit(): void
    {
        $withdrawal      = $this->getRandomWithdrawal();
        $withdrawalArray = $this->getRandomWithdrawalAsArray();
        $asset           = $this->getRandomAsset();
        $budget          = $this->getRandomBudget();

        $journalRepos = $this->mockDefaultSession();
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);


        $collector = $this->mock(GroupCollectorInterface::class);
        $collector->shouldReceive('setTypes')
                  ->withArgs([[TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]])->atLeast()->once()->andReturnSelf();

        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withTagInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setJournalIds')->withArgs([[$withdrawal->id]])->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$withdrawalArray]);

        $repository->shouldReceive('getAccountsByType')->atLeast()->once()->andReturn(new Collection([$asset]));
        $budgetRepos->shouldReceive('getBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('transactions.mass.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        $response->assertSee('Edit a number of transactions');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\MassController
     */
    public function testUpdate(): void
    {
        $deposit       = $this->getRandomDeposit();
        $repository    = $this->mockDefaultSession();
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $updateService = $this->mock(JournalUpdateService::class);

        $this->expectsEvents(UpdatedTransactionGroup::class);

        $updateService->shouldReceive('setTransactionJournal')->atLeast()->once();
        $updateService->shouldReceive('setData')->atLeast()->once();
        $updateService->shouldReceive('update')->atLeast()->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('findNull')->atLeast()->once()->andReturn($deposit);

        $this->session(['transactions.mass-edit.uri' => 'http://localhost']);

        $data = [
            'journals'       => [$deposit->id],
            'description'    => [$deposit->id => 'Updated salary thing'],
            'amount'         => [$deposit->id => 1600],
            'date'           => [$deposit->id => '2014-07-24'],
            'source_name'    => [$deposit->id => 'Job'],
            'destination_id' => [$deposit->id => 1],
            'category'       => [$deposit->id => 'Salary'],
        ];

        $this->be($this->user());
        $response = $this->post(route('transactions.mass.update', [$deposit->id]), $data);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }
}

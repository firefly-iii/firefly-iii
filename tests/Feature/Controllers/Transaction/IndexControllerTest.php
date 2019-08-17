<?php
/**
 * IndexControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Transaction;


use Amount;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class IndexControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Transaction\IndexController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $group     = $this->getRandomWithdrawalGroup();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $collector = $this->mock(GroupCollectorInterface::class);

        // generic set for the info blocks:
        $groupArray = [
            $this->getRandomWithdrawalAsArray(),
            $this->getRandomDepositAsArray(),
            $this->getRandomTransferAsArray(),
        ];

        // role?
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true);

        // make paginator.
        $paginator = new LengthAwarePaginator([$group], 1, 40, 1);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('10');

        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->atLeast()->once()->andReturn($paginator);
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($groupArray);


        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['withdrawal']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\IndexController
     */
    public function testIndexDeposit(): void
    {
        $this->mockDefaultSession();
        $group     = $this->getRandomWithdrawalGroup();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $collector = $this->mock(GroupCollectorInterface::class);

        // generic set for the info blocks:
        $groupArray = [
            $this->getRandomWithdrawalAsArray(),
            $this->getRandomDepositAsArray(),
            $this->getRandomTransferAsArray(),
        ];

        // role?
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true);

        // make paginator.
        $paginator = new LengthAwarePaginator([$group], 1, 40, 1);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('10');

        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->atLeast()->once()->andReturn($paginator);
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($groupArray);


        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['deposit']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\IndexController
     */
    public function testIndexTransfers(): void
    {
        $this->mockDefaultSession();
        $group     = $this->getRandomWithdrawalGroup();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $collector = $this->mock(GroupCollectorInterface::class);

        // generic set for the info blocks:
        $groupArray = [
            $this->getRandomWithdrawalAsArray(),
            $this->getRandomDepositAsArray(),
            $this->getRandomTransferAsArray(),
        ];

        // role?
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true);

        // make paginator.
        $paginator = new LengthAwarePaginator([$group], 1, 40, 1);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('10');

        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->atLeast()->once()->andReturn($paginator);
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn($groupArray);


        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfers']));
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\IndexController
     */
    public function testIndexAll(): void
    {
        $this->mockDefaultSession();
        $group     = $this->getRandomWithdrawalGroup();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $collector = $this->mock(GroupCollectorInterface::class);

        // role?
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true);

        // make paginator.
        $paginator = new LengthAwarePaginator([$group], 1, 40, 1);

        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->atLeast()->once()->andReturn($paginator);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $this->be($this->user());
        $response = $this->get(route('transactions.index.all', ['withdrawal']));
        $response->assertStatus(200);
    }

}
<?php
/**
 * ShowControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Account;

use Amount;
use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class ShowControllerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testShow(string $range): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff:
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $repository = $this->mock(AccountRepositoryInterface::class);
        $journal    = $this->getRandomWithdrawalAsArray();
        $group      = $this->getRandomWithdrawalGroup();
        $asset      = $this->getRandomAsset();
        $euro       = $this->getEuro();

        $this->mockDefaultSession();

        // amount mocks:
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $repository->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date)->once();

        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        $this->mockLastActivity();
        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$journal]);
        $collector->shouldReceive('withAccountInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([$group], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [$asset->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testShowAll(string $range): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);
        // mock stuff:
        $this->mock(AccountTaskerInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $repository = $this->mock(AccountRepositoryInterface::class);
        $journal    = $this->getRandomWithdrawalAsArray();
        $group      = $this->getRandomWithdrawalGroup();
        $euro       = $this->getEuro();
        $asset      = $this->getRandomAsset();

        $this->mockDefaultSession();

        $repository->shouldReceive('isLiability')->andReturn(false)->atLeast()->once();
        $repository->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date)->once();

        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$journal]);
        $collector->shouldReceive('withAccountInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([$group], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show.all', [$asset->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}

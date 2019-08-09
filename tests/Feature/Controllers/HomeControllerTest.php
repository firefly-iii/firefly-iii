<?php
/**
 * HomeControllerTest.php
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

namespace Tests\Feature\Controllers;

use Amount;
use Event;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 * Class HomeControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HomeControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\HomeController
     */
    public function testDateRange(): void
    {
        $this->mockDefaultSession();
        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
        ];

        $response = $this->post(route('daterange'), $args);
        $response->assertStatus(200);
        $response->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController
     */
    public function testDateRangeCustom(): void
    {
        $this->mockDefaultSession();
        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
            'label' => 'Custom range',
        ];

        $response = $this->post(route('daterange'), $args);
        $response->assertStatus(200);
        $response->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\HomeController
     * @covers       \FireflyIII\Http\Controllers\Controller
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndex(string $range): void
    {
        Event::fake();
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_index');
        $account = $this->getRandomAsset();

        $pref       = new Preference;
        $pref->data = [$account->id];
        Preferences::shouldReceive('get')->withArgs(['frontPageAccounts', [$account->id]])->atLeast()->once()->andReturn($pref);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('5');
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $billRepos    = $this->mock(BillRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $euro         = $this->getEuro();


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $accountRepos->shouldReceive('count')->andReturn(1)->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection([$account]))->atLeast()->once();
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]))->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->andReturn($euro)->atLeast()->once();
        $billRepos->shouldReceive('getBills')->andReturn(new Collection)->atLeast()->once();
//        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn($euro);


        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getGroups')->atLeast()->once()->andReturn(new Collection);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        Event::assertDispatched(RequestedVersionCheckStatus::class);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\HomeController
     * @covers       \FireflyIII\Http\Controllers\Controller
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndexEmpty(string $range): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_index');
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('count')->andReturn(0);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('index'));
        $response->assertStatus(302);
    }


}

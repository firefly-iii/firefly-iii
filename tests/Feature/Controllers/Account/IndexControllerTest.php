<?php
/**
 * IndexControllerTest.php
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

namespace Tests\Feature\Controllers\Account;

use Amount;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;


/**
 * Class IndexControllerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @covers       \FireflyIII\Http\Controllers\Account\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     */
    public function testIndex(string $range): void
    {
        // mock stuff
        $this->mock(CurrencyRepositoryInterface::class);
        $account    = $this->getRandomAsset();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $euro       = $this->getEuro();
        // mock hasRole for user repository:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $repository->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $repository->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        Steam::shouldReceive('balancesByAccounts')->andReturn([$account->id => '100']);
        Steam::shouldReceive('getLastActivities')->andReturn([]);

        // mock default session stuff
        $this->mockDefaultSession();

        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        Amount::shouldReceive('formatAnything')->andReturn('123');

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'interest_period'])->andReturn('monthly');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'account_number'])->andReturn('123');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.index', ['asset']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}

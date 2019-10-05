<?php
/**
 * ReconcileControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 * Class ConfigurationControllerTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReconcileControllerTest extends TestCase
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
     * Test showing the reconciliation.
     *
     * @covers \FireflyIII\Http\Controllers\Account\ReconcileController
     */
    public function testReconcile(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $this->mock(GroupCollectorInterface::class);
        $euro  = $this->getEuro();
        $asset = $this->getRandomAsset();
        $date  = new Carbon;


        $userRepos->shouldReceive('hasRole')->atLeast()->once()->withArgs([Mockery::any(), 'owner'])->andReturnTrue();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('100');
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);

        // mock default session stuff
        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile', [$asset->id, '20170101', '20170131']));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * Submit reconciliation.
     *
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController
     * @covers       \FireflyIII\Http\Requests\ReconciliationStoreRequest
     */
    public function testSubmit(): void
    {
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $journalRepos = $this->mockDefaultSession();
        $asset        = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $date         = new Carbon;
        $factory      = $this->mock(TransactionGroupFactory::class);
        $group        = $this->getRandomWithdrawalGroup();
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);

        Preferences::shouldReceive('mark')->atLeast()->once();


        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $journalRepos->shouldReceive('reconcileById')->times(3);
        $repository->shouldReceive('getReconciliation')->atLeast()->once()->andReturn($asset);
        $repository->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $factory->shouldReceive('setUser')->atLeast()->once();
        $factory->shouldReceive('create')->andReturn($group);


        $data = [
            'journals'   => [1, 2, 3],
            'reconcile'  => 'create',
            'difference' => '5',
            'start'      => '20170101',
            'end'        => '20170131',
        ];
        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.submit', [$asset->id, '20170101', '20170131']), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * Submit reconciliation, but throw an error.
     *
     * @covers       \FireflyIII\Http\Controllers\Account\ReconcileController
     * @covers       \FireflyIII\Http\Requests\ReconciliationStoreRequest
     */
    public function testSubmitError(): void
    {
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $journalRepos = $this->mockDefaultSession();
        $asset        = $this->getRandomAsset();
        $euro         = $this->getEuro();
        $date         = new Carbon;
        $factory      = $this->mock(TransactionGroupFactory::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);

        Preferences::shouldReceive('mark')->atLeast()->once();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $journalRepos->shouldReceive('reconcileById')->times(3);
        $repository->shouldReceive('getReconciliation')->atLeast()->once()->andReturn($asset);
        $repository->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $factory->shouldReceive('setUser')->atLeast()->once();
        $factory->shouldReceive('create')->andThrow(new FireflyException('Some error'));

        $data = [
            'journals'   => [1, 2, 3],
            'reconcile'  => 'create',
            'difference' => '5',
            'start'      => '20170101',
            'end'        => '20170131',
        ];
        $this->be($this->user());
        $response = $this->post(route('accounts.reconcile.submit', [$asset->id, '20170101', '20170131']), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
}

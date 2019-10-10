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

namespace Tests\Feature\Controllers\Json;


use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;
use Steam;

/**
 *
 * Class ReconcileControllerTest
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
     * Test overview of reconciliation.
     *
     * @covers \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testOverview(): void
    {
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $euro         = $this->getEuro();
        $withdrawal   = $this->getRandomWithdrawalAsArray();
        $date         = new Carbon;
        $account      = $this->user()->accounts()->where('id', $withdrawal['source_account_id'])->first();

        // make sure it falls into the current range
        $withdrawal['date'] = new Carbon('2017-01-30');

        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);

        $collector->shouldReceive('setJournalIds')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$withdrawal]);

        Amount::shouldReceive('formatAnything')->andReturn('-100');
        Amount::shouldReceive('getDefaultCurrency')->andReturn($euro);


        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'journals'     => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [$account->id, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(200);
        $response->assertSee('-100');
    }

    /**
     * List transactions for reconciliation view.
     *
     * @covers \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testTransactions(): void
    {
        $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $date         = new Carbon;
        $euro         = $this->getEuro();
        $withdrawal   = $this->getRandomWithdrawalAsArray();

        Steam::shouldReceive('balance')->atLeast()->once()->andReturn('20');


        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->andReturn('-100');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        //$accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);


        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$withdrawal]);


        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [1, '20170101', '20170131']));
        $response->assertStatus(200);
        $response->assertSee('-100');
    }
}

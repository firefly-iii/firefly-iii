<?php
/**
 * ReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;
use Mockery;
use Steam;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 */
class ReportControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController
     */
    public function testNetWorth(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $currencyRepos->shouldReceive('setUser');
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();

        // mock calls:
        $accountRepos->shouldReceive('setUser');

        $accountRepos->shouldReceive('getMetaValue')->times(2)
                     ->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1', '0');
        $accountRepos->shouldReceive('getMetaValue')
                     ->withArgs([Mockery::any(), 'currency_id'])->andReturn(1);
        $accountRepos->shouldReceive('getMetaValue')
                     ->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');


        Steam::shouldReceive('balancesByAccounts')->andReturn(['5', '10']);
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.net-worth', ['1,2', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController
     */
    public function testOperations(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $tasker    = $this->mock(AccountTaskerInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $income    = [1 => ['sum' => '100']];
        $expense   = [2 => ['sum' => '-100']];
        $tasker->shouldReceive('getIncomeReport')->once()->andReturn($income);
        $tasker->shouldReceive('getExpenseReport')->once()->andReturn($expense);
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.operations', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController
     */
    public function testSum(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $tasker    = $this->mock(AccountTaskerInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        $income  = [];
        $expense = [];
        $tasker->shouldReceive('getIncomeReport')->andReturn($income)->times(1);
        $tasker->shouldReceive('getExpenseReport')->andReturn($expense)->times(1);

        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.sum', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }
}

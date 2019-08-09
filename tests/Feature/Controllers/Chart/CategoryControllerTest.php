<?php
/**
 * CategoryControllerTest.php
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

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws FireflyException
     */
    public function testAll(string $range): void
    {

        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $firstUseDate = null;
        switch ($range) {
            default:
                throw new FireflyException(sprintf('No case for %s', $range));
            case '1D':
                $firstUseDate = Carbon::now()->subDays(3);
                break;
            case '1W':
                $firstUseDate = Carbon::now()->subDays(12);
                break;
            case '1M':
                $firstUseDate = Carbon::now()->subDays(40);
                break;
            case '3M':
                $firstUseDate = Carbon::now()->subDays(120);
                break;
            case '6M':
                $firstUseDate = Carbon::now()->subDays(160);
                break;
            case '1Y':
                $firstUseDate = Carbon::now()->subDays(365);
                break;
            case 'custom':
                $firstUseDate = Carbon::now()->subDays(20);
                break;
        }

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('spentInPeriod')->andReturn('0')->atLeast()->once();
        $repository->shouldReceive('earnedInPeriod')->andReturn('0')->atLeast()->once();
        $repository->shouldReceive('firstUseDate')->andReturn($firstUseDate)->once();
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();
        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontPage(string $range): void
    {
        $repository    = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $generator     = $this->mock(GeneratorInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        // spent per currency data:
        $spentNoCategory = [
            1 =>
                [
                    'spent'                   => '-123.45',
                    'currency_id'             => 1,
                    'currency_code'           => 'X',
                    'currency_symbol'         => 'x',
                    'currency_decimal_places' => 2,
                ],
        ];
        $spentData       = [
            1 => [
                'name'  => 'Car',
                'spent' => [
                    1 => [
                        'spent'                   => '-123.45',
                        'currency_id'             => 2,
                        'currency_code'           => 'a',
                        'currency_symbol'         => 'b',
                        'currency_decimal_places' => 2,
                    ],
                ],
            ],
        ];

        // grab two categories from the user
        $categories = $this->user()->categories()->take(2)->get();

        // grab two the users asset accounts:
        $accounts = $this->user()->accounts()->where('account_type_id', 3)->take(2)->get();

        // repository will return these.
        $repository->shouldReceive('getCategories')->andReturn($categories)->once();
        $accountRepos->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn($accounts);

        $repository->shouldReceive('spentInPeriodPerCurrency')->times(2)->andReturn($spentData);
        $repository->shouldReceive('spentInPeriodPcWoCategory')->once()->andReturn($spentNoCategory);

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->once()->andReturn($this->getEuro());
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.frontpage', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController
     */
    public function testReportPeriod(): void
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('periodExpenses')->andReturn([])->once();
        $repository->shouldReceive('periodIncome')->andReturn([])->once();
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.period', [1, '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController
     */
    public function testReportPeriodNoCategory(): void
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('periodExpensesNoCategory')->andReturn([])->once();
        $repository->shouldReceive('periodIncomeNoCategory')->andReturn([])->once();
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.period.no-category', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testSpecificPeriod(string $range): void
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $account      = $this->getRandomAsset();
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('earnedInPeriod')->andReturn('0');
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.specific', ['1', '2012-01-01']));
        $response->assertStatus(200);
    }
}

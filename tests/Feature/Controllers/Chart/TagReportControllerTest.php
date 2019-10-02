<?php
/**
 * TagReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Tag\OperationsRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Tests\Support\TestDataTrait;
use Tests\TestCase;

/**
 * Class TagReportControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TagReportControllerTest extends TestCase
{
    use TestDataTrait;
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testBudgetExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);


        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->tagListExpenses());


        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $this->mockDefaultSession();

        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.budget-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testCategoryExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->tagListExpenses());

        $this->mockDefaultSession();

        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.category-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * TODO something in this test sometimes gives a 404 but not sure yet what it is.
     *
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testMainChart(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->tagListExpenses());
        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->tagListIncome());

        $withdrawal  = $this->getRandomWithdrawalAsArray();
        $tag         = $this->user()->tags()->where('tag', 'Expensive')->first();
        $date        = new Carbon;
        $false       = new Preference;
        $false->data = false;

        $this->mockDefaultSession();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($false);

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiSet')->andReturn([])->once()->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.main', ['1', $tag->id, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testTagExpense(): void
    {
        $this->mockDefaultSession();
        $generator = $this->mock(GeneratorInterface::class);
        $tagRepos  = $this->mock(TagRepositoryInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->tagListExpenses());

        $this->mock(AccountRepositoryInterface::class);

        $tag = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.tag-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testTagIncome(): void
    {
        $this->mockDefaultSession();
        $generator    = $this->mock(GeneratorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);

        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->tagListIncome());

        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.tag-income', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }
}

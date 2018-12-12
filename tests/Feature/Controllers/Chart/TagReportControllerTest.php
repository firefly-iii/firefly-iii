<?php
/**
 * TagReportControllerTest.php
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
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class TagReportControllerTest
 */
class TagReportControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testAccountExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'account'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());


        $response = $this->get(route('chart.tag.account-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testAccountIncome(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['income', 'account'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());

        $response = $this->get(route('chart.tag.account-income', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testBudgetExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'budget'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

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
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'category'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.category-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testMainChart(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $set = new Collection;
        for ($i = 0; $i < 10; ++$i) {
            $transaction = factory(Transaction::class)->make();
            $tag         = factory(Tag::class)->make();
            $transaction->transactionJournal->tags()->save($tag);
            $set->push($transaction);
        }
        $tag = $this->user()->tags()->first();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([TransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([OpposingAccountFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([PositiveAmountFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([NegativeAmountFilter::class])->andReturnSelf();
        $collector->shouldReceive('setTags')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($set);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.main', ['1', $tag->tag, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testTagExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'tag'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.tag-expense', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\TagReportController
     */
    public function testTagIncome(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $pieChart     = $this->mock(MetaPieChartInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tag          = $this->user()->tags()->first();
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setTags')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['income', 'tag'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.tag.tag-income', ['1', $tag->tag, '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }
}

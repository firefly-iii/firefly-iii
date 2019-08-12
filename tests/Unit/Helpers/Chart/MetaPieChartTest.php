<?php
/**
 * MetaPieChartTest.php
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

namespace Tests\Unit\Helpers\Chart;

use Carbon\Carbon;
use FireflyIII\Helpers\Chart\MetaPieChart;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class MetaPieChartTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaPieChartTest extends TestCase
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
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart
     */
    public function testGenerateExpenseAccount(): void
    {
        $som   = (new Carbon())->startOfMonth();
        $eom   = (new Carbon())->endOfMonth();
        $one   = $this->getRandomAsset();
        $two   = $this->getRandomAsset($one->id);
        $array = [
            [
                'destination_account_id' => $one->id,
                'budget_id'              => 1,
                'category_id'            => 1,
                'amount'                 => '10',
            ],
            [
                'destination_account_id' => $two->id,
                'budget_id'              => 2,
                'category_id'            => 2,
                'amount'                 => '10',
            ],
        ];

        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock collector so the correct set of journals is returned:
        // then verify the results.

        $collector->shouldReceive('setUser')->andReturnSelf()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('setBudgets')->andReturnSelf()->once();
        $collector->shouldReceive('setCategories')->andReturnSelf()->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($array)->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();

        // mock all repositories:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$one->id])->andReturn($one)->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$two->id])->andReturn($two)->atLeast()->once();

        /** @var MetaPieChart $helper */
        $helper = app(MetaPieChart::class);
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $chart = $helper->generate('expense', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertContains($keys[0], [$one->name, $two->name]);
        $this->assertContains($keys[1], [$one->name, $two->name]);

        $this->assertEquals('10.000000000000', $chart[$one->name]);
        $this->assertEquals('10.000000000000', $chart[$two->name]);

        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart
     */
    public function testGenerateExpenseAccountOthers(): void
    {
        $som   = (new Carbon())->startOfMonth();
        $eom   = (new Carbon())->endOfMonth();
        $one   = $this->getRandomAsset();
        $two   = $this->getRandomAsset($one->id);
        $array = [
            [
                'destination_account_id' => $one->id,
                'budget_id'              => 1,
                'category_id'            => 1,
                'amount'                 => '10',
            ],
            [
                'destination_account_id' => $two->id,
                'budget_id'              => 2,
                'category_id'            => 2,
                'amount'                 => '10',
            ],
        ];

        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock collector so the correct set of journals is returned:
        // then verify the results.

        $collector->shouldReceive('setUser')->andReturnSelf()->twice();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->twice();
        $collector->shouldReceive('setRange')->andReturnSelf()->twice();
        $collector->shouldReceive('setBudgets')->andReturnSelf()->once();
        $collector->shouldReceive('setCategories')->andReturnSelf()->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($array)->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();

        // also collect others:
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('getSum')->atLeast()->once()->andReturn('100');

        // mock all repositories:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$one->id])->andReturn($one)->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$two->id])->andReturn($two)->atLeast()->once();

        /** @var MetaPieChart $helper */
        $helper = app(MetaPieChart::class);
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $helper->setCollectOtherObjects(true);
        $chart = $helper->generate('expense', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertContains($keys[0], [$one->name, $two->name]);
        $this->assertContains($keys[1], [$one->name, $two->name]);

        $this->assertEquals('10.000000000000', $chart[$one->name]);
        $this->assertEquals('10.000000000000', $chart[$two->name]);

        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart
     */
    public function testGenerateIncomeAccountOthers(): void
    {
        $som   = (new Carbon())->startOfMonth();
        $eom   = (new Carbon())->endOfMonth();
        $one   = $this->getRandomAsset();
        $two   = $this->getRandomAsset($one->id);
        $array = [
            [
                'destination_account_id' => $one->id,
                'budget_id'              => 1,
                'category_id'            => 1,
                'amount'                 => '10',
            ],
            [
                'destination_account_id' => $two->id,
                'budget_id'              => 2,
                'category_id'            => 2,
                'amount'                 => '10',
            ],
        ];

        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock collector so the correct set of journals is returned:
        // then verify the results.

        $collector->shouldReceive('setUser')->andReturnSelf()->twice();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->twice();
        $collector->shouldReceive('setRange')->andReturnSelf()->twice();
        $collector->shouldReceive('setBudgets')->andReturnSelf()->once();
        $collector->shouldReceive('setCategories')->andReturnSelf()->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($array)->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();

        // also collect others:
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf()->once();
        $collector->shouldReceive('getSum')->atLeast()->once()->andReturn('100');

        // mock all repositories:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$one->id])->andReturn($one)->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$two->id])->andReturn($two)->atLeast()->once();

        /** @var MetaPieChart $helper */
        $helper = app(MetaPieChart::class);
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $helper->setCollectOtherObjects(true);
        $chart = $helper->generate('income', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertContains($keys[0], [$one->name, $two->name]);
        $this->assertContains($keys[1], [$one->name, $two->name]);

        $this->assertEquals('10.000000000000', $chart[$one->name]);
        $this->assertEquals('10.000000000000', $chart[$two->name]);

        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Helpers\Chart\MetaPieChart
     */
    public function testGenerateIncomeAccount(): void
    {
        $som   = (new Carbon())->startOfMonth();
        $eom   = (new Carbon())->endOfMonth();
        $one   = $this->getRandomAsset();
        $two   = $this->getRandomAsset($one->id);
        $array = [
            [
                'destination_account_id' => $one->id,
                'budget_id'              => 1,
                'category_id'            => 1,
                'amount'                 => '10',
            ],
            [
                'destination_account_id' => $two->id,
                'budget_id'              => 2,
                'category_id'            => 2,
                'amount'                 => '10',
            ],
        ];

        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock collector so the correct set of journals is returned:
        // then verify the results.

        $collector->shouldReceive('setUser')->andReturnSelf()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('setBudgets')->andReturnSelf()->once();
        $collector->shouldReceive('setCategories')->andReturnSelf()->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($array)->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();

        // mock all repositories:
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$one->id])->andReturn($one)->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->withArgs([$two->id])->andReturn($two)->atLeast()->once();

        /** @var MetaPieChart $helper */
        $helper = app(MetaPieChart::class);
        $helper->setUser($this->user());
        $helper->setStart($som);
        $helper->setEnd($eom);
        $chart = $helper->generate('income', 'account');

        // since the set is pretty basic the result is easy to validate:
        $keys = array_keys($chart);
        $this->assertContains($keys[0], [$one->name, $two->name]);
        $this->assertContains($keys[1], [$one->name, $two->name]);

        $this->assertEquals('10.000000000000', $chart[$one->name]);
        $this->assertEquals('10.000000000000', $chart[$two->name]);

        $this->assertTrue(true);
    }
}

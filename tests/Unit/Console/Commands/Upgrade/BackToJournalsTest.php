<?php
/**
 * BackToJournalsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use Log;
use Tests\TestCase;

/**
 * Class BackToJournalsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BackToJournalsTest extends TestCase
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
     * Perfect run. Will report on nothing.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\BackToJournals
     */
    public function testHandle(): void
    {
        // verify preference:
        $false       = new Configuration;
        $false->data = false;
        $true        = new Configuration;
        $true->data  = true;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_back_to_journals', false])->andReturn($false);
        FireflyConfig::shouldReceive('get')->withArgs(['4780_migrated_to_groups', false])->andReturn($true);

        // set new preference after running:
        FireflyConfig::shouldReceive('set')->withArgs(['4780_back_to_journals', true]);

        $this->artisan('firefly-iii:back-to-journals')
             ->expectsOutput('Check 0 transaction journal(s) for budget info.')
             ->expectsOutput('Check 0 transaction journal(s) for category info.')
             ->assertExitCode(0);

    }

    /**
     * Transaction has a budget, journal doesn't.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\BackToJournals
     */
    public function testHandleBudget(): void
    {
        $journal = $this->getRandomWithdrawal();
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();
        $transaction->budgets()->sync([$budget->id]);
        $journal->budgets()->sync([]);
        $journal->save();
        $transaction->save();


        // verify preference:
        $false       = new Configuration;
        $false->data = false;
        $true        = new Configuration;
        $true->data  = true;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_back_to_journals', false])->andReturn($false);
        FireflyConfig::shouldReceive('get')->withArgs(['4780_migrated_to_groups', false])->andReturn($true);

        // set new preference after running:
        FireflyConfig::shouldReceive('set')->withArgs(['4780_back_to_journals', true]);

        $this->artisan('firefly-iii:back-to-journals')
             ->expectsOutput('Check 1 transaction journal(s) for budget info.')
             ->expectsOutput('Check 0 transaction journal(s) for category info.')
             ->assertExitCode(0);

        // transaction should have no budget:
        $this->assertEquals(0, $transaction->budgets()->count());
        // journal should have one.
        $this->assertEquals(1, $journal->budgets()->count());
        // should be $budget:
        $this->assertEquals($budget->id, $journal->budgets()->first()->id);
    }

    /**
     * Transaction has a category, journal doesn't.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\BackToJournals
     */
    public function testHandleCategory(): void
    {
        $journal = $this->getRandomWithdrawal();
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Category $category */
        $category = $this->user()->categories()->first();
        $transaction->categories()->sync([$category->id]);
        $journal->categories()->sync([]);
        $journal->save();
        $transaction->save();

        // verify preference:
        $false       = new Configuration;
        $false->data = false;
        $true        = new Configuration;
        $true->data  = true;

        FireflyConfig::shouldReceive('get')->withArgs(['4780_back_to_journals', false])->andReturn($false);
        FireflyConfig::shouldReceive('get')->withArgs(['4780_migrated_to_groups', false])->andReturn($true);

        // set new preference after running:
        FireflyConfig::shouldReceive('set')->withArgs(['4780_back_to_journals', true]);

        $this->artisan('firefly-iii:back-to-journals')
             ->expectsOutput('Check 0 transaction journal(s) for budget info.')
             ->expectsOutput('Check 1 transaction journal(s) for category info.')
             ->assertExitCode(0);

        // transaction should have no category:
        $this->assertEquals(0, $transaction->categories()->count());
        // journal should have one.
        $this->assertEquals(1, $journal->categories()->count());
        // should be $category:
        $this->assertEquals($category->id, $journal->categories()->first()->id);
    }

    /**
     * Transaction has a budget, journal has another
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\BackToJournals
     */
    public function testHandleDifferentBudget(): void
    {
        $journal = $this->getRandomWithdrawal();
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Budget $budget */
        $budget      = $this->user()->budgets()->first();
        $otherBudget = $this->user()->budgets()->where('id', '!=', $budget->id)->first();
        $transaction->budgets()->sync([$budget->id]);
        $journal->budgets()->sync([$otherBudget->id]);
        $journal->save();
        $transaction->save();


        // verify preference:
        $false       = new Configuration;
        $false->data = false;
        $true        = new Configuration;
        $true->data  = true;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_back_to_journals', false])->andReturn($false);
        FireflyConfig::shouldReceive('get')->withArgs(['4780_migrated_to_groups', false])->andReturn($true);

        // set new preference after running:
        FireflyConfig::shouldReceive('set')->withArgs(['4780_back_to_journals', true]);

        $this->artisan('firefly-iii:back-to-journals')
             ->expectsOutput('Check 1 transaction journal(s) for budget info.')
             ->expectsOutput('Check 0 transaction journal(s) for category info.')
             ->assertExitCode(0);

        // transaction should have no budget:
        $this->assertEquals(0, $transaction->budgets()->count());
        // journal should have one.
        $this->assertEquals(1, $journal->budgets()->count());
        // should be $budget:
        $this->assertEquals($budget->id, $journal->budgets()->first()->id);

    }


    /**
     * Transaction has a category, journal has another
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\BackToJournals
     */
    public function testHandleDifferentCategory(): void
    {
        $journal = $this->getRandomWithdrawal();
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->first();
        /** @var Category $category */
        $category      = $this->user()->categories()->first();
        $otherCategory = $this->user()->categories()->where('id', '!=', $category->id)->first();
        $transaction->categories()->sync([$category->id]);
        $journal->categories()->sync([$otherCategory->id]);
        $journal->save();
        $transaction->save();

        // verify preference:
        $false       = new Configuration;
        $false->data = false;
        $true        = new Configuration;
        $true->data  = true;
        FireflyConfig::shouldReceive('get')->withArgs(['4780_back_to_journals', false])->andReturn($false);
        FireflyConfig::shouldReceive('get')->withArgs(['4780_migrated_to_groups', false])->andReturn($true);

        // set new preference after running:
        FireflyConfig::shouldReceive('set')->withArgs(['4780_back_to_journals', true]);

        $this->artisan('firefly-iii:back-to-journals')
             ->expectsOutput('Check 0 transaction journal(s) for budget info.')
             ->expectsOutput('Check 1 transaction journal(s) for category info.')
             ->assertExitCode(0);

        // transaction should have no category:
        $this->assertEquals(0, $transaction->categories()->count());
        // journal should have one.
        $this->assertEquals(1, $journal->categories()->count());
        // should be $category:
        $this->assertEquals($category->id, $journal->categories()->first()->id);

    }

}
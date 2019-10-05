<?php
/**
 * BudgetLimitCurrencyTest.php
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

namespace Tests\Unit\Console\Commands\Upgrade;


use Amount;
use FireflyConfig;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Configuration;
use Log;
use Tests\TestCase;

/**
 * Class BudgetLimitCurrencyTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetLimitCurrencyTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Upgrade\BudgetLimitCurrency
     */
    public function testHandle(): void
    {
        BudgetLimit::whereNull('transaction_currency_id')->forceDelete();

        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_bl_currency', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_bl_currency', true]);

        $this->artisan('firefly-iii:bl-currency')
             ->expectsOutput('All budget limits are correct.')
             ->assertExitCode(0);
    }

    /**
     * Create a bad budget limit.
     * @covers \FireflyIII\Console\Commands\Upgrade\BudgetLimitCurrency
     */
    public function testHandleBadLimit(): void
    {
        $false       = new Configuration;
        $false->data = false;
        $budget      = $this->user()->budgets()->first();
        $limit       = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'amount'     => '10',
                'start_date' => '2019-01-01',
                'end_date'   => '2019-01-31',
            ]);

        FireflyConfig::shouldReceive('get')->withArgs(['480_bl_currency', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_bl_currency', true]);

        $currency = $this->getEuro();
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($currency);

        $this->artisan('firefly-iii:bl-currency')
             ->expectsOutput(
                 sprintf('Budget limit #%d (part of budget "%s") now has a currency setting (%s).',
                         $limit->id, $budget->name, 'Euro'
                 )
             )
             ->assertExitCode(0);

        // assume currency is filled in.
        $this->assertCount(1, BudgetLimit::where('id', $limit->id)->where('transaction_currency_id', 1)->get());
    }
}

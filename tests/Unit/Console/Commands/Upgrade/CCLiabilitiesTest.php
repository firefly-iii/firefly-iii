<?php
/**
 * CCLiabilitiesTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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


use FireflyConfig;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Configuration;
use Log;
use Tests\TestCase;

/**
 * Class CCLiabilitiesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CCLiabilitiesTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Upgrade\CCLiabilities
     */
    public function testHandle(): void
    {
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_cc_liabilities', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_cc_liabilities', true]);


        $this->artisan('firefly-iii:cc-liabilities')
             ->expectsOutput('No incorrectly stored credit card liabilities.')
             ->assertExitCode(0);

        // nothing changed, so nothing to verify.
    }

    /**
     * Add type to make the script run.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\CCLiabilities
     */
    public function testHandleEmpty(): void
    {
        $type        = AccountType::create(
            [
                'type' => AccountType::CREDITCARD,
            ]
        );
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_cc_liabilities', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_cc_liabilities', true]);


        $this->artisan('firefly-iii:cc-liabilities')
             ->expectsOutput('No incorrectly stored credit card liabilities.')
             ->assertExitCode(0);
        $type->forceDelete();
        // nothing changed, so nothing to verify.
    }

    /**
     * Add some things to make it trigger.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\CCLiabilities
     */
    public function testHandleCase(): void
    {
        $type = AccountType::create(
            [
                'type' => AccountType::CREDITCARD,
            ]
        );

        $account = Account::create(
            [
                'name'            => 'CC',
                'user_id'         => 1,
                'account_type_id' => $type->id,

            ]
        );

        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_cc_liabilities', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_cc_liabilities', true]);


        $this->artisan('firefly-iii:cc-liabilities')
             ->expectsOutput(sprintf('Converted credit card liability account "%s" (#%d) to generic debt liability.', $account->name, $account->id))
             ->expectsOutput('Credit card liability types are no longer supported and have been converted to generic debts. See: http://bit.ly/FF3-credit-cards')
             ->assertExitCode(0);

        // verify new type.
        $this->assertCount(0, Account::where('id', $account->id)->where('account_type_id', $type->id)->get());

        $account->forceDelete();
        $type->forceDelete();

    }
}

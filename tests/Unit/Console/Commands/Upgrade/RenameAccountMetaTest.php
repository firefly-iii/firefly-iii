<?php
declare(strict_types=1);
/**
 * RenameAccountMetaTest.php
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
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Configuration;
use Log;
use Tests\TestCase;

/**
 * Class RenameAccountMetaTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RenameAccountMetaTest extends TestCase
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
     * Basic test. Assume nothing is wrong.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\RenameAccountMeta
     */
    public function testHandle(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_rename_account_meta', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_rename_account_meta', true]);

        // assume all is well.
        $this->artisan('firefly-iii:rename-account-meta')
             ->expectsOutput('All account meta is OK.')
             ->assertExitCode(0);

    }

    /**
     * Create bad entry, then check if its renamed.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\RenameAccountMeta
     */
    public function testHandleRename(): void
    {
        $false        = new Configuration;
        $false->data  = false;
        // check config
        FireflyConfig::shouldReceive('get')->withArgs(['480_rename_account_meta', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_rename_account_meta', true]);


        $expense = $this->getRandomExpense();

        $meta = AccountMeta::create(
            [
                'name'       => 'accountRole',
                'data'       => 'defaultAsset',
                'account_id' => $expense->id,
            ]
        );


        // assume all is well.
        $this->artisan('firefly-iii:rename-account-meta')
             ->expectsOutput('Renamed 1 account meta entries (entry).')
             ->assertExitCode(0);
        $this->assertCount(0, AccountMeta::where('id', $meta->id)->where('name', 'accountRole')->get());
        $this->assertCount(1, AccountMeta::where('id', $meta->id)->where('name', 'account_role')->get());

        $meta->forceDelete();
    }

}

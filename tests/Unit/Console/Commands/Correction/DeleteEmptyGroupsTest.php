<?php
/**
 * DeleteEmptyGroupsTest.php
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\TransactionGroup;
use Log;
use Tests\TestCase;

/**
 * Class DeleteEmptyGroupsTest
 */
class DeleteEmptyGroupsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyGroups
     */
    public function testHandle(): void
    {
        // assume there are no empty groups..
        $this->artisan('firefly-iii:delete-empty-groups')
             ->expectsOutput('No empty transaction groups.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyGroups
     */
    public function testHandleWithGroup(): void
    {
        // create new group:
        $group = TransactionGroup::create(['user_id' => 1]);

        // command should delete it.
        $this->artisan('firefly-iii:delete-empty-groups')
             ->expectsOutput('Deleted 1 empty transaction group(s).')
             ->assertExitCode(0);

        // should not be able to find it:
        $this->assertCount(0, TransactionGroup::where('id', $group->id)->whereNull('deleted_at')->get());
    }
}
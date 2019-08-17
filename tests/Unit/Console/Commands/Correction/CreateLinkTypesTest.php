<?php
declare(strict_types=1);
/**
 * CreateLinkTypesTest.php
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


use FireflyIII\Models\LinkType;
use Log;
use Tests\TestCase;

/**
 * Class CreateLinkTypesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateLinkTypesTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\CreateLinkTypes
     */
    public function testHandle(): void
    {
        // delete all other link types:
        LinkType::whereNotIn('name', ['Related', 'Refund', 'Paid', 'Reimbursement'])->forceDelete();

        // delete link type:
        LinkType::where('name', 'Reimbursement')->forceDelete();
        $this->assertCount(3, LinkType::get());

        // run command, expect output:
        $this->artisan('firefly-iii:create-link-types')
             ->expectsOutput('Created missing link type "Reimbursement"')
             ->assertExitCode(0);

        $this->assertCount(4, LinkType::get());
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CreateLinkTypes
     */
    public function testHandleNothing(): void
    {
        $this->assertCount(4, LinkType::get());

        // run command, expect output:
        $this->artisan('firefly-iii:create-link-types')
             ->expectsOutput('All link types OK!')
             ->assertExitCode(0);

        $this->assertCount(4, LinkType::get());
    }

}

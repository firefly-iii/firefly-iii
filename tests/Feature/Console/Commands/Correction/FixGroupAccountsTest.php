<?php
/*
 * FixGroupAccountsTest.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace Tests\Feature\Console\Commands\Correction;


use Tests\TestCase;

/**
 * Class FixGroupAccountsTest
 * @package Tests\Feature\Console\Commands\Correction
 */
class FixGroupAccountsTest extends TestCase
{

    /**
     * @covers \FireflyIII\Console\Commands\Correction\FixGroupAccounts
     */
    public function testHandle(): void
    {
        // basic group with multiple journals, should trigger event.
        $this->artisan('firefly-iii:unify-group-accounts')
             ->expectsOutput('Updated inconsistent transaction groups.')
             ->assertExitCode(0);

        // This just triggers the events. No real test.
    }


}
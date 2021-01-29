<?php

/*
 * CorrectOpeningBalanceCurrenciesTest.php
 * Copyright (c) 2021 james@firefly-iii.org
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
/*
 * CorrectOpeningBalanceCurrenciesTest.php
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


use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 * Class CorrectOpeningBalanceCurrenciesTest
 *
 * @package Tests\Feature\Console\Commands\Correction
 */
class CorrectOpeningBalanceCurrenciesTest extends TestCase
{

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CorrectOpeningBalanceCurrencies
     */
    public function testBasic(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CorrectOpeningBalanceCurrencies
     */
    public function testHandleOK(): void
    {
        // run command
        $this->artisan('firefly-iii:fix-ob-currencies')
             ->expectsOutput('There was nothing to fix in the opening balance transactions.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CorrectOpeningBalanceCurrencies
     */
    public function testHandleBroken(): void
    {
        // create opening balance journal for test. Is enough to trigger this test.
        TransactionJournal::factory()->openingBalance()->create();

        // run command
        $this->artisan('firefly-iii:fix-ob-currencies')
             ->expectsOutput('Corrected 1 opening balance transaction(s).')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CorrectOpeningBalanceCurrencies
     */
    public function testHandleNoAccount(): void
    {
        Log::debug('Now in testHandleNoAccount');
        // create opening balance journal for test. Is enough to trigger this test.
        $journal = TransactionJournal::factory()->brokenOpeningBalance()->create();

        // run command
        $this->artisan('firefly-iii:fix-ob-currencies')
             ->expectsOutput(sprintf('Transaction journal #%d has no valid account. Cant fix this line.', $journal->id))
            //->expectsOutput('Cant fix this line.')
             ->assertExitCode(0);

        $journal->forceDelete();
    }

}

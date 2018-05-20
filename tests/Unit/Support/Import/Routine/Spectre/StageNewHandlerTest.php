<?php
/**
 * StageNewHandlerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace tests\Unit\Support\Import\Routine\Spectre;


use FireflyIII\Models\ImportJob;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Support\Import\Information\GetSpectreCustomerTrait;
use FireflyIII\Support\Import\Routine\Spectre\StageNewHandler;
use Tests\TestCase;
use Preferences;
/**
 * Class StageNewHandlerTest
 */
class StageNewHandlerTest extends TestCase
{

    // todo run() with zero logins and an existing customer (must be retrieved from Spectre).
    // todo run() with one login and an existing customer (must be retrieved from Spectre).

    /**
     * run() with zero logins and a non-existing customer (must be created by Spectre).
     *
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageNewHandler
     */
    public function testRunBasic(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sn_a_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock classes:
        $trait     = $this->mock(GetSpectreCustomerTrait::class);
        $llRequest = $this->mock(ListLoginsRequest::class);
        $lcRequest = $this->mock(ListCustomersRequest::class);

        // mock calls for list logins
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([]);

        // mock call for preferences
        // todo here we are
        Preferences::shouldReceive('getForUser');


        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        $handler->run();
    }
}
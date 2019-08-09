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

namespace Tests\Unit\Support\Import\Routine\Spectre;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Attempt;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Holder;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use FireflyIII\Support\Import\Routine\Spectre\StageNewHandler;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class StageNewHandlerTest
 */
class StageNewHandlerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }
    // todo run() with zero logins and an existing customer (must be retrieved from Spectre).
    // todo run() with one login and an existing customer (must be retrieved from Spectre).

    /**
     * run() with zero logins and a non-existing customer (must be created by Spectre).
     *
     * @covers \FireflyIII\Support\Import\Information\GetSpectreCustomerTrait
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageNewHandler
     */
    public function testRunBasic(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sn_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // fake Spectre customer:
        $fakeCustomer = new Customer(
            [
                'id'         => 1,
                'identifier' => 'fake',
                'secret'     => 'Dumbledore dies',
            ]
        );

        // mock classes:
        $llRequest  = $this->mock(ListLoginsRequest::class);
        $lcRequest  = $this->mock(ListCustomersRequest::class);
        $ncRequest  = $this->mock(NewCustomerRequest::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);


        // mock calls for list logins (return empty list for now).
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([]);

        // mock calls for list customers (return empty list).
        $lcRequest->shouldReceive('setUser')->once();
        $lcRequest->shouldReceive('call')->once();
        $lcRequest->shouldReceive('getCustomers')->once()->andReturn([]);

        // create new customer:
        $ncRequest->shouldReceive('setUser')->once();
        $ncRequest->shouldReceive('getCustomer')->once()->andReturn($fakeCustomer);
        $ncRequest->shouldReceive('call')->once();

        // mock calls for repository:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn([]);

        // mock call for preferences
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'spectre_customer', null])->andReturnNull();


        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * run() with zero logins and an existing customer (from preferences).
     *
     * @covers \FireflyIII\Support\Import\Information\GetSpectreCustomerTrait
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageNewHandler
     */
    public function testRunExistingCustomer(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sn_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $fakeCustomerPreference       = new Preference;
        $fakeCustomerPreference->name = 'spectre_customer';
        $fakeCustomerPreference->data = [
            'id'         => 1,
            'identifier' => 'fake',
            'secret'     => 'Dumbledore dies',
        ];

        // mock classes:
        $llRequest  = $this->mock(ListLoginsRequest::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);


        // mock calls for list logins (return empty list for now).
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([]);

        // mock call for preferences
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'spectre_customer', null])->andReturn($fakeCustomerPreference);

        // mock calls for repository:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn([]);

        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * run() with zero logins and multiple customers at Spectre (none in prefs)
     *
     * @covers \FireflyIII\Support\Import\Information\GetSpectreCustomerTrait
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageNewHandler
     */
    public function testRunMultiCustomer(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sn_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // fake Spectre customer:
        $fakeCustomer = new Customer(
            [
                'id'         => 1,
                'identifier' => 'fake',
                'secret'     => 'Dumbledore dies',
            ]
        );

        $correctCustomer = new Customer(
            [
                'id'         => 1,
                'identifier' => 'default_ff3_customer',
                'secret'     => 'Firefly III',
            ]
        );

        // mock classes:
        $llRequest  = $this->mock(ListLoginsRequest::class);
        $lcRequest  = $this->mock(ListCustomersRequest::class);
        $ncRequest  = $this->mock(NewCustomerRequest::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for list logins (return empty list for now).
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([]);

        // mock calls for list customers (return empty list).
        $lcRequest->shouldReceive('setUser')->once();
        $lcRequest->shouldReceive('call')->once();
        $lcRequest->shouldReceive('getCustomers')->once()->andReturn([$fakeCustomer, $correctCustomer]);

        // mock calls for repository:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn([]);

        // mock call for preferences
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'spectre_customer', null])->andReturnNull()->once();
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'spectre_customer', $correctCustomer->toArray()])->once();

        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * run() with one login and multiple customers at Spectre (none in prefs)
     *
     * @covers \FireflyIII\Support\Import\Information\GetSpectreCustomerTrait
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageNewHandler
     */
    public function testRunMultiCustomerLogin(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sn_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // fake Spectre customer:
        $fakeCustomer = new Customer(
            [
                'id'         => 1,
                'identifier' => 'fake',
                'secret'     => 'Dumbledore dies',
            ]
        );

        $correctCustomer = new Customer(
            [
                'id'         => 1,
                'identifier' => 'default_ff3_customer',
                'secret'     => 'Firefly III',
            ]
        );

        // fake login:
        $holder  = new Holder([]);
        $attempt = new Attempt(
            [
                'api_mode'                  => 'x',
                'api_version'               => 4,
                'automatic_fetch'           => true,
                'categorize'                => true,
                'created_at'                => '2018-05-21 12:00:00',
                'consent_given_at'          => '2018-05-21 12:00:00',
                'consent_types'             => ['transactions'],
                'custom_fields'             => [],
                'daily_refresh'             => true,
                'device_type'               => 'mobile',
                'user_agent'                => 'Mozilla/x',
                'remote_ip'                 => '127.0.0.1',
                'exclude_accounts'          => [],
                'fail_at'                   => '2018-05-21 12:00:00',
                'fail_error_class'          => 'err',
                'fail_message'              => 'message',
                'fetch_scopes'              => [],
                'finished'                  => true,
                'finished_recent'           => true,
                'from_date'                 => '2018-05-21 12:00:00',
                'id'                        => 1,
                'interactive'               => true,
                'locale'                    => 'en',
                'partial'                   => true,
                'show_consent_confirmation' => true,
                'stages'                    => [],
                'store_credentials'         => true,
                'success_at'                => '2018-05-21 12:00:00',
                'to_date'                   => '2018-05-21 12:00:00',
                'updated_at'                => '2018-05-21 12:00:00',
            ]
        );
        $login   = new Login(
            [
                'consent_given_at'          => '2018-05-21 12:00:00',
                'consent_types'             => ['transactions'],
                'country_code'              => 'NL',
                'created_at'                => '2018-05-21 12:00:00',
                'updated_at'                => '2018-05-21 12:00:00',
                'customer_id'               => '1',
                'daily_refresh'             => true,
                'holder_info'               => $holder->toArray(),
                'id'                        => 123,
                'last_attempt'              => $attempt->toArray(),
                'last_success_at'           => '2018-05-21 12:00:00',
                'next_refresh_possible_at'  => '2018-05-21 12:00:00',
                'provider_code'             => 'XF',
                'provider_id'               => '123',
                'provider_name'             => 'Fake',
                'show_consent_confirmation' => true,
                'status'                    => 'active',
                'store_credentials'         => true,
            ]
        );

        // mock classes:
        $llRequest  = $this->mock(ListLoginsRequest::class);
        $lcRequest  = $this->mock(ListCustomersRequest::class);
        $ncRequest  = $this->mock(NewCustomerRequest::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for list logins (return empty list for now).
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([$login]);

        // mock calls for list customers (return empty list).
        $lcRequest->shouldReceive('setUser')->once();
        $lcRequest->shouldReceive('call')->once();
        $lcRequest->shouldReceive('getCustomers')->once()->andReturn([$fakeCustomer, $correctCustomer]);

        // mock call for preferences
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'spectre_customer', null])->andReturnNull()->once();
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'spectre_customer', $correctCustomer->toArray()])->once();

        // mock calls for repository:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn([]);
        $repository->shouldReceive('setConfiguration')->once()
                   ->withArgs([Mockery::any(), ['all-logins' => [$login->toArray()]]]);

        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}

<?php
/**
 * ChooseLoginHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\Spectre;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Attempt;
use FireflyIII\Services\Spectre\Object\Holder;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class ChooseLoginHandlerTest
 */
class ChooseLoginHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler
     */
    public function testCCFalse(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'slh-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        $handler = new ChooseLoginHandler;
        $handler->setImportJob($job);
        $this->assertFalse($handler->configurationComplete());
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler
     */
    public function testCCTrue(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'slh-B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = ['selected-login' => 1,];
        $job->save();

        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        $handler = new ChooseLoginHandler;
        $handler->setImportJob($job);
        $this->assertTrue($handler->configurationComplete());
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler
     */
    public function testConfigureJob(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'slh-C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $data       = [
            'spectre_login_id' => 12,
        ];
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')->withArgs([Mockery::any(), ['selected-login' => 12],])->once();
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'authenticated']);

        $handler = new ChooseLoginHandler;
        $handler->setImportJob($job);
        try {
            $this->assertCount(0, $handler->configureJob($data));
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler
     */
    public function testConfigureJobCustomer(): void
    {
        // fake Spectre customer:
        $fakeCustomerPreference       = new Preference;
        $fakeCustomerPreference->name = 'spectre_customer';
        $fakeCustomerPreference->data = [
            'id'         => 1,
            'identifier' => 'fake',
            'secret'     => 'Dumbledore dies',
        ];

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'slh-C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $data   = [
            'spectre_login_id' => 0,
        ];
        $carbon = new Carbon();
        $token  = new Token(['token' => 'x', 'expires_at' => $carbon->toW3cString(), 'connect_url' => 'https://']);

        // should try to grab customer from preferences:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'spectre_customer', null])
                   ->andReturn($fakeCustomerPreference)->once();

        // mock stuff
        $ctRequest  = $this->mock(CreateTokenRequest::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), ['selected-login' => 0,]]);
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'do-authenticate']);
        $repository->shouldReceive('setConfiguration')->once()->withArgs(
            [Mockery::any(),
             [
                 'selected-login' => 0,
                 'customer'       => ['id' => 1, 'identifier' => 'fake', 'secret' => 'Dumbledore dies',],
                 'token'          => ['token' => 'x', 'expires_at' => $carbon->toW3cString(), 'connect_url' => 'https://'],
             ]]
        );

        // should try to grab token from Spectre:
        $ctRequest->shouldReceive('setUser')->once();
        $ctRequest->shouldReceive('setCustomer')->once();
        $ctRequest->shouldReceive('setUri')->once()->withArgs([route('import.job.status.index', [$job->key])]);
        $ctRequest->shouldReceive('call')->once();
        $ctRequest->shouldReceive('getToken')->once()->andReturn($token);


        $handler = new ChooseLoginHandler;
        $handler->setImportJob($job);
        try {
            $this->assertCount(0, $handler->configureJob($data));
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler
     */
    public function testGetNextData(): void
    {
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
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

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'slh-C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'all-logins' => [$login->toArray()],
        ];
        $job->save();

        $handler = new ChooseLoginHandler;
        $handler->setImportJob($job);
        $this->assertEquals(['logins' => [$login]], $handler->getNextData());
    }

}

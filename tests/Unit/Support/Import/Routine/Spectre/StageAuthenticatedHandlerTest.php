<?php
/**
 * StageAuthenticatedHandlerTest.php
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

namespace Tests\Unit\Support\Import\Routine\Spectre;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account as SpectreAccount;
use FireflyIII\Services\Spectre\Object\Attempt;
use FireflyIII\Services\Spectre\Object\Holder;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListAccountsRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Support\Import\Routine\Spectre\StageAuthenticatedHandler;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class StageAuthenticatedHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StageAuthenticatedHandlerTest extends TestCase
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
     * Already have logins in configuration.
     *
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageAuthenticatedHandler
     */
    public function testRunBasicHaveLogins(): void
    {
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
                'id'                        => 1234,
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
        $job->key           = 'sa_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'all-logins'     => [
                $login->toArray(),
            ],
            'selected-login' => 1234,
        ];
        $job->save();

        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $laRequest  = $this->mock(ListAccountsRequest::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setLogin')->withArgs([Mockery::any()])->once();
        $laRequest->shouldReceive('call')->once();
        $laRequest->shouldReceive('getAccounts')->once()->andReturn([$spectreAccount]);

        $expected = [
            'all-logins'     => [
                $login->toArray(),
            ],
            'selected-login' => 1234,
            'accounts'       => [
                0 => $spectreAccount->toArray(),
            ],
        ];

        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), $expected])->once();

        $handler = new StageAuthenticatedHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * Already have logins in configuration, but none selected.
     *
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageAuthenticatedHandler
     */
    public function testRunBasicHaveLoginsNull(): void
    {
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
                'id'                        => 1234,
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
        $job->key           = 'sa_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'all-logins'     => [
                $login->toArray(),
            ],
            'selected-login' => 0,
        ];
        $job->save();

        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $laRequest  = $this->mock(ListAccountsRequest::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setLogin')->withArgs([Mockery::any()])->once();
        $laRequest->shouldReceive('call')->once();
        $laRequest->shouldReceive('getAccounts')->once()->andReturn([$spectreAccount]);

        $expected = [
            'all-logins'     => [
                $login->toArray(),
            ],
            'selected-login' => 0,
            'accounts'       => [
                0 => $spectreAccount->toArray(),
            ],
        ];

        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), $expected])->once();

        $handler = new StageAuthenticatedHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * No logins in config, will grab them from Spectre.
     *
     * @covers \FireflyIII\Support\Import\Routine\Spectre\StageAuthenticatedHandler
     */
    public function testRunBasicZeroLogins(): void
    {
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
                'id'                        => 1234,
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
        $job->key           = 'sa_a_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'all-logins'     => [],
            'selected-login' => 0,
        ];
        $job->save();

        // needs to be a full spectre account this time.
        $spectreAccount = new SpectreAccount(
            [
                'id'            => 1234,
                'login_id'      => 5678,
                'currency_code' => 'EUR',
                'balance'       => 1000,
                'name'          => 'Fake Spectre Account',
                'nature'        => 'account',
                'created_at'    => '2018-01-01 12:12:12',
                'updated_at'    => '2018-01-01 12:12:12',
                'extra'         => [],
            ]
        );
        // mock preference
        $fakeCustomerPreference       = new Preference;
        $fakeCustomerPreference->name = 'spectre_customer';
        $fakeCustomerPreference->data = [
            'id'         => 1,
            'identifier' => 'fake',
            'secret'     => 'Dumbledore dies',
        ];

        // mock call for preferences
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'spectre_customer', null])->andReturn($fakeCustomerPreference);

        // mock repository:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $laRequest  = $this->mock(ListAccountsRequest::class);
        $llRequest  = $this->mock(ListLoginsRequest::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setUser')->once();
        $laRequest->shouldReceive('setLogin')->withArgs([Mockery::any()])->once();
        $laRequest->shouldReceive('call')->once();
        $laRequest->shouldReceive('getAccounts')->once()->andReturn([$spectreAccount]);

        // mock calls for list logins (return empty list for now).
        $llRequest->shouldReceive('setUser')->once();
        $llRequest->shouldReceive('setCustomer')->once();
        $llRequest->shouldReceive('call')->once();
        $llRequest->shouldReceive('getLogins')->once()->andReturn([$login]);

        $expected = [
            'all-logins'     => [
                $login->toArray(),
            ],
            'selected-login' => 0,
            'accounts'       => [
                0 => $spectreAccount->toArray(),
            ],
        ];

        $repository->shouldReceive('setConfiguration')
                   ->withArgs([Mockery::any(), $expected])->once();

        $handler = new StageAuthenticatedHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

}

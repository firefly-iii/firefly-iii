<?php
/**
 * ChooseAccountsHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\Spectre;


use Amount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account as SpectreAccount;
use FireflyIII\Services\Spectre\Object\Attempt;
use FireflyIII\Services\Spectre\Object\Holder;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ChooseAccountsHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChooseAccountsHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testCCFalse(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'account_mapping' => [],
        ];
        $job->save();

        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();

        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $this->assertFalse($handler->configurationComplete());

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testCCTrue(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'account_mapping' => [
                4 => 6,
            ],
        ];
        $job->save();

        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setStage')->withArgs([Mockery::any(), 'go-for-import'])->once();

        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $this->assertTrue($handler->configurationComplete());
    }

    /**
     * Case: Local account is valid. Spectre account is valid.
     *
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testConfigureJob(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-c' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts' => [
                0 => [
                    'id'   => 3131,
                    'name' => 'Some fake account',
                ],
            ],
        ];
        $job->save();

        $account = $this->user()->accounts()->inRandomOrder()->first();

        // data to submit:
        $data = [
            'account_mapping' => [3131 => 872,],
            'apply_rules'     => true,
        ];
        // expected configuration:
        $config = [
            'accounts'        => [0 => ['id' => 3131, 'name' => 'Some fake account',],],
            'account_mapping' => [3131 => 872,],
            'apply-rules'     => true,
        ];


        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([872])->andReturn($account);
        $importRepos->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $config]);


        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $this->assertCount(0, $handler->configureJob($data));
    }

    /**
     * Case: Local account is invalid. Spectre account is invalid.
     *
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testConfigureJobInvalidBoth(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-E' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts' => [
                0 => [
                    'id'   => 3134,
                    'name' => 'Some fake account',
                ],
            ],
        ];
        $job->save();

        // data to submit:
        $data = [
            'account_mapping' => [3131 => 872,],
            'apply_rules'     => true,
        ];
        // expected configuration:
        $config = [
            'accounts'        => [0 => ['id' => 3134, 'name' => 'Some fake account',],],
            'account_mapping' => [0 => 0,],
            'apply-rules'     => true,
        ];


        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([872])->andReturn(null);
        $importRepos->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $config]);


        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $result = $handler->configureJob($data);
        $this->assertCount(1, $result);
        $this->assertEquals('It seems you have not selected any accounts to import from.', $result->first());
    }

    /**
     * Case: Local account is invalid. Spectre account is valid.
     *
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testConfigureJobInvalidLocal(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-D' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts' => [
                0 => [
                    'id'   => 3131,
                    'name' => 'Some fake account',
                ],
            ],
        ];
        $job->save();

        // data to submit:
        $data = [
            'account_mapping' => [3131 => 872,],
            'apply_rules'     => true,
        ];
        // expected configuration:
        $config = [
            'accounts'        => [0 => ['id' => 3131, 'name' => 'Some fake account',],],
            'account_mapping' => [3131 => 0,],
            'apply-rules'     => true,
        ];


        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([872])->andReturn(null);
        $importRepos->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $config]);


        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $this->assertCount(0, $handler->configureJob($data));
    }

    /**
     * Case: Local account is valid. Spectre account is invalid.
     *
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testConfigureJobInvalidSpectre(): void
    {
        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-E' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts' => [
                0 => [
                    'id'   => 3134,
                    'name' => 'Some fake account',
                ],
            ],
        ];
        $job->save();

        $account = $this->user()->accounts()->inRandomOrder()->first();

        // data to submit:
        $data = [
            'account_mapping' => [3131 => 872,],
            'apply_rules'     => true,
        ];
        // expected configuration:
        $config = [
            'accounts'        => [0 => ['id' => 3134, 'name' => 'Some fake account',],],
            'account_mapping' => [0 => 872,],
            'apply-rules'     => true,
        ];


        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([872])->andReturn($account);
        $importRepos->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $config]);


        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $this->assertCount(0, $handler->configureJob($data));
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testGetNextData(): void
    {
        // needs to be a full spectre account this time.
        $spectreAccount = [
            'id'            => 1234,
            'login_id'      => 5678,
            'currency_code' => 'EUR',
            'balance'       => 1000,
            'name'          => 'Fake Spectre Account',
            'nature'        => 'account',
            'created_at'    => '2018-01-01 12:12:12',
            'updated_at'    => '2018-01-01 12:12:12',
            'extra'         => [],
        ];


        // need to be a full spectre login this time.
        $holder       = new Holder([]);
        $attempt      = new Attempt(
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
        $spectreLogin = new Login(
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

        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-F' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts'       => [
                0 => $spectreAccount,
            ],
            'all-logins'     => [
                0 => $spectreLogin->toArray(),
            ],
            'selected-login' => 1234,
        ];
        $job->save();

        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();

        $euro   = $this->getEuro();
        $usd    = $this->getDollar();
        $first  = $this->user()->accounts()->where('account_type_id', 3)->first();
        $second = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $first->id)->first();
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]])
                     ->once()->andReturn(new Collection([$first, $second]));
        $accountRepos->shouldReceive('getMetaValue')->twice()->withArgs([Mockery::any(), 'currency_id'])
                     ->andReturn(1, 2);
        $currencyRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn($euro);
        $currencyRepos->shouldReceive('findNull')->once()->withArgs([2])->andReturn(null);
        Amount::shouldReceive('getDefaultCurrencyByUser')->withArgs([Mockery::any()])->once()->andReturn($usd);

        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $result = [];
        try {
            $result = $handler->getNextData();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

        $expected = [
            'accounts'    => [
                0 => new SpectreAccount($spectreAccount),
            ],
            'ff_accounts' => [
                $first->id  => [
                    'name' => $first->name,
                    'iban' => $first->iban,
                    'code' => $euro->code,
                ],
                $second->id => [
                    'name' => $second->name,
                    'iban' => $second->iban,
                    'code' => $usd->code,
                ],
            ],
            'login'       => $spectreLogin,
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Select first login.
     *
     * @covers \FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler
     */
    public function testGetNextDataZero(): void
    {
        // needs to be a full spectre account this time.
        $spectreAccount = [
            'id'            => 1234,
            'login_id'      => 5678,
            'currency_code' => 'EUR',
            'balance'       => 1000,
            'name'          => 'Fake Spectre Account',
            'nature'        => 'account',
            'created_at'    => '2018-01-01 12:12:12',
            'updated_at'    => '2018-01-01 12:12:12',
            'extra'         => [],
        ];


        // need to be a full spectre login this time.
        $holder       = new Holder([]);
        $attempt      = new Attempt(
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
        $spectreLogin = new Login(
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

        // fake job:
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'sca-F' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [
            'accounts'       => [
                0 => $spectreAccount,
            ],
            'all-logins'     => [
                0 => $spectreLogin->toArray(),
            ],
            'selected-login' => 0,
        ];
        $job->save();

        // mock repositories:
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $importRepos   = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setUser')->once();

        $euro   = $this->getEuro();
        $usd    = $this->getDollar();
        $first  = $this->user()->accounts()->where('account_type_id', 3)->first();
        $second = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $first->id)->first();
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]])
                     ->once()->andReturn(new Collection([$first, $second]));
        $accountRepos->shouldReceive('getMetaValue')->twice()->withArgs([Mockery::any(), 'currency_id'])
                     ->andReturn(1, 2);
        $currencyRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn($euro);
        $currencyRepos->shouldReceive('findNull')->once()->withArgs([2])->andReturn(null);
        Amount::shouldReceive('getDefaultCurrencyByUser')->withArgs([Mockery::any()])->once()->andReturn($usd);

        // call handler:
        $handler = new ChooseAccountsHandler();
        $handler->setImportJob($job);
        $result = [];
        try {
            $result = $handler->getNextData();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

        $expected = [
            'accounts'    => [
                0 => new SpectreAccount($spectreAccount),
            ],
            'ff_accounts' => [
                $first->id  => [
                    'name' => $first->name,
                    'iban' => $first->iban,
                    'code' => $euro->code,
                ],
                $second->id => [
                    'name' => $second->name,
                    'iban' => $second->iban,
                    'code' => $usd->code,
                ],
            ],
            'login'       => $spectreLogin,
        ];

        $this->assertEquals($expected, $result);
    }

}

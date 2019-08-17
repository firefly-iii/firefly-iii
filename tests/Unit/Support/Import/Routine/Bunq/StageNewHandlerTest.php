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

namespace Tests\Unit\Support\Import\Routine\Bunq;


use bunq\Model\Generated\Endpoint\BunqResponseMonetaryAccountList;
use bunq\Model\Generated\Endpoint\MonetaryAccount as BunqMonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank as BunqMonetaryAccountBank;
use bunq\Model\Generated\Endpoint\MonetaryAccountJoint;
use bunq\Model\Generated\Endpoint\MonetaryAccountLight;
use bunq\Model\Generated\Object\CoOwner;
use bunq\Model\Generated\Object\LabelUser;
use bunq\Model\Generated\Object\MonetaryAccountSetting;
use bunq\Model\Generated\Object\Pointer;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\Bunq\MonetaryAccount;
use FireflyIII\Support\Import\Routine\Bunq\StageNewHandler;
use Log;
use Mockery;
use Preferences;
use Tests\Object\FakeApiContext;
use Tests\TestCase;

/**
 * Class StageNewHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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

    /**
     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageNewHandler
     */
    public function testRun(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'snh_bunq_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $pref       = new Preference;
        $pref->name = 'some-name';
        $pref->data = '{"a": "b"}';

        // create fake bunq object:
        $setting = new MonetaryAccountSetting(null, null, null);
        $mab     = new BunqMonetaryAccountBank('EUR', 'Some descr', null, null, null, null, null, null, null, null);
        $ma      = new BunqMonetaryAccount;
        $alias   = new Pointer('a', 'b', null);


        // dont care about deprecation.
        $alias->setType('IBAN');
        $alias->setName('Somebody');
        $alias->setValue('SM72C9584723533916792029340');
        $setting->setColor('FFFFFF');
        $mab->setSetting($setting);
        $mab->setAlias([$alias]);
        $ma->setMonetaryAccountBank($mab);

        // response list.
        $list = new BunqResponseMonetaryAccountList([$ma], []);

        $expectedConfig = [
            'accounts' => [
                0 => [
                    'id'            => null,
                    'currency_code' => null,
                    'description'   => null,
                    'balance'       => null,
                    'status'        => null,
                    'type'          => 'MonetaryAccountBank',
                    'iban'          => 'SM72C9584723533916792029340',
                    'aliases'       => [
                        [
                            'name'  => $alias->getName(),
                            'type'  => $alias->getType(),
                            'value' => $alias->getValue(),
                        ],
                    ],
                    'settings'      => [
                        'color'                 => $setting->getColor(),
                        'default_avatar_status' => null,
                        'restriction_chat'      => null,
                    ],
                ],
            ],
        ];

        // mock classes
        $apiContext = $this->mock(ApiContext::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $mAccount   = $this->mock(MonetaryAccount::class);

        // mock calls
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->once()->andReturn($pref);
        $apiContext->shouldReceive('fromJson')->withArgs(['{"a": "b"}'])->once()->andReturn(new FakeApiContext);
        $repository->shouldReceive('setUser')->once();
        $mAccount->shouldReceive('listing')->andReturn($list)->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn([]);

        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedConfig]);

        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageNewHandler
     */
    public function testRunMaj(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'snha_bunq_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $pref       = new Preference;
        $pref->name = 'some-name';
        $pref->data = '{"a": "b"}';

        // create fake bunq object:
        $setting   = new MonetaryAccountSetting(null, null, null);
        $maj       = new MonetaryAccountJoint('EUR', [], 'Some descr', null, null, null, null, null, null, null, null);
        $ma        = new BunqMonetaryAccount;
        $alias     = new Pointer('a', 'b', null);
        $labelUser = new LabelUser('x', 'James', 'NL');
        $coOwner   = new CoOwner($alias);

        // dont care about deprecation.
        $alias->setType('IBAN');
        $alias->setName('Somebody');
        $alias->setValue('SM72C9584723533916792029340');
        $labelUser->setDisplayName('James');
        $setting->setColor('FFFFFF');
        $maj->setSetting($setting);
        $maj->setAlias([$alias]);
        $maj->setAllCoOwner([$coOwner]);
        $ma->setMonetaryAccountJoint($maj);
        $coOwner->setAlias($labelUser);

        // response list.
        $list = new BunqResponseMonetaryAccountList([$ma], []);

        $expectedConfig = [
            'accounts' => [
                0 => [
                    'id'            => null,
                    'currency_code' => null,
                    'description'   => null,
                    'balance'       => null,
                    'status'        => null,
                    'type'          => 'MonetaryAccountJoint',
                    'co-owners'     => ['James'],
                    'aliases'       => [
                        [
                            'name'  => $alias->getName(),
                            'type'  => $alias->getType(),
                            'value' => $alias->getValue(),
                        ],
                    ],
                    'settings'      => [
                        'color'                 => $setting->getColor(),
                        'default_avatar_status' => null,
                        'restriction_chat'      => null,
                    ],
                    'iban'          => 'SM72C9584723533916792029340',
                ],
            ],
        ];

        // mock classes
        $apiContext = $this->mock(ApiContext::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $mAccount   = $this->mock(MonetaryAccount::class);

        // mock calls
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->once()->andReturn($pref);
        $apiContext->shouldReceive('fromJson')->withArgs(['{"a": "b"}'])->once()->andReturn(new FakeApiContext);
        $repository->shouldReceive('setUser')->once();
        $mAccount->shouldReceive('listing')->andReturn($list)->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn([]);
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedConfig]);


        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\Bunq\StageNewHandler
     */
    public function testRunMal(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'snh_bbunq_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $pref       = new Preference;
        $pref->name = 'some-name';
        $pref->data = '{"a": "b"}';

        // create fake bunq object:
        $setting = new MonetaryAccountSetting(null, null, null);
        $mal     = new MonetaryAccountLight('EUR', 'Some descr', null, null, null, null, null, null, null, null);
        $ma      = new BunqMonetaryAccount;
        $alias   = new Pointer('a', 'b', null);


        // dont care about deprecation.
        $alias->setType('IBAN');
        $alias->setName('Somebody');
        $alias->setValue('SM72C9584723533916792029340');
        $setting->setColor('FFFFFF');
        $mal->setSetting($setting);
        $mal->setAlias([$alias]);
        $ma->setMonetaryAccountLight($mal);

        // response list.
        $list = new BunqResponseMonetaryAccountList([$ma], []);

        $expectedConfig = [
            'accounts' => [
                0 => [
                    'id'            => null,
                    'currency_code' => null,
                    'description'   => null,
                    'balance'       => null,
                    'status'        => null,
                    'type'          => 'MonetaryAccountLight',
                    'aliases'       => [
                        [
                            'name'  => $alias->getName(),
                            'type'  => $alias->getType(),
                            'value' => $alias->getValue(),
                        ],
                    ],
                    'settings'      => [
                        'color'                 => $setting->getColor(),
                        'default_avatar_status' => null,
                        'restriction_chat'      => null,
                    ],
                    'iban'          => 'SM72C9584723533916792029340',
                ],

            ],
        ];

        // mock classes
        $apiContext = $this->mock(ApiContext::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $mAccount   = $this->mock(MonetaryAccount::class);

        // mock calls
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->once()->andReturn($pref);
        $apiContext->shouldReceive('fromJson')->withArgs(['{"a": "b"}'])->once()->andReturn(new FakeApiContext);
        $repository->shouldReceive('setUser')->once();
        $mAccount->shouldReceive('listing')->andReturn($list)->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn([]);
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedConfig]);


        $handler = new StageNewHandler;
        $handler->setImportJob($job);
        try {
            $handler->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }


}

<?php
/**
 * BunqPrerequisitesTest.php
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

namespace Tests\Unit\Import\Prerequisites;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Prerequisites\BunqPrerequisites;
use FireflyIII\Models\Preference;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\IP\IPRetrievalInterface;
use Log;
use Mockery;
use Preferences;
use Tests\Object\FakeApiContext;
use Tests\TestCase;

/**
 * Class BunqPrerequisitesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BunqPrerequisitesTest extends TestCase
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
     * Has no API key, has no external IP.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testGetViewParameters(): void
    {
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturnNull()->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturnNull()->twice();

        $service = $this->mock(IPRetrievalInterface::class);
        $service->shouldReceive('getIP')->once()->andReturn('10.0.0.15');

        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $parameters = $object->getViewParameters();
        $this->assertEquals(['api_key' => '', 'external_ip' => '10.0.0.15'], $parameters);
    }

    /**
     * Has empty API key, has empty external IP.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testGetViewParametersEmpty(): void
    {
        $pref       = new Preference;
        $pref->name = 'dontmatter';
        $pref->data = '';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturn($pref)->twice();

        $service = $this->mock(IPRetrievalInterface::class);
        $service->shouldReceive('getIP')->once()->andReturn('10.0.0.15');

        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $parameters = $object->getViewParameters();
        $this->assertEquals(['api_key' => '', 'external_ip' => '10.0.0.15'], $parameters);
    }

    /**
     * Has API key, has external IP.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testGetViewParametersFilled(): void
    {
        $pref       = new Preference;
        $pref->name = 'dontmatter';
        $pref->data = 'data';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturn($pref)->times(2);
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturn($pref)->times(3);

        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $parameters = $object->getViewParameters();
        $this->assertEquals(['api_key' => 'data', 'external_ip' => 'data'], $parameters);
    }

    /**
     * API context empty
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testIsCompleteEmpty(): void
    {
        $pref       = new Preference;
        $pref->name = 'dontmatter';
        $pref->data = 'data';

        $empty       = new Preference;
        $empty->name = 'dontmatter';
        $empty->data = '';

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($empty)->once();
        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * API context filled
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testIsCompleteFilled(): void
    {
        $pref       = new Preference;
        $pref->name = 'dontmatter';
        $pref->data = 'data';

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturn($pref)->once();
        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $this->assertTrue($object->isComplete());
    }

    /**
     * API context null.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testIsCompleteNull(): void
    {
        $pref       = new Preference;
        $pref->name = 'dontmatter';
        $pref->data = 'data';

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_external_ip', null])->andReturn($pref)->once();
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'bunq_api_context', null])->andReturnNull()->once();
        $object = new BunqPrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * Test call to API.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testStorePrerequisites(): void
    {
        $object = new BunqPrerequisites;
        $object->setUser($this->user());

        $data = [
            'api_key'     => 'Some API key',
            'external_ip' => '10.0.0.15',
        ];

        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'bunq_api_key', $data['api_key']])->once();
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'bunq_external_ip', $data['external_ip']])->once();
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'bunq_api_context', '{"a":"b"}'])->once();

        // create fake context
        $context = $this->mock(ApiContext::class);
        $context->shouldReceive('create')
                ->withArgs([Mockery::any(), 'Some API key', 'Firefly III v' . config('firefly.version'), [$data['external_ip']]])
                ->once()->andReturn(new FakeApiContext());
        $messages = $object->storePrerequisites($data);
        $this->assertEquals('', $messages->first());
        $this->assertCount(0, $messages);
    }

    /**
     * Test call that throws error.
     *
     * @covers \FireflyIII\Import\Prerequisites\BunqPrerequisites
     */
    public function testStorePrerequisitesExp(): void
    {
        $object = new BunqPrerequisites;
        $object->setUser($this->user());

        $data = [
            'api_key'     => 'Some API key',
            'external_ip' => '10.0.0.15',
        ];

        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'bunq_api_key', $data['api_key']])->once();
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'bunq_external_ip', $data['external_ip']])->once();

        // create fake context
        $context = $this->mock(ApiContext::class);
        $context->shouldReceive('create')
                ->withArgs([Mockery::any(), 'Some API key', 'Firefly III v' . config('firefly.version'), [$data['external_ip']]])
                ->once()->andThrow(new FireflyException('Some exception'));
        $messages = $object->storePrerequisites($data);
        $this->assertEquals('Some exception', $messages->first());
        $this->assertCount(1, $messages);
    }
}

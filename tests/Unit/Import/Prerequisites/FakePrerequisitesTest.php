<?php
/**
 * FakePrerequisitesTest.php
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


use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Models\Preference;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class FakePrerequisitesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FakePrerequisitesTest extends TestCase
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
     * Bad API key length in preferences
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testGetViewParametersBadLength(): void
    {
        // API key should be empty:
        $apiPref       = new Preference;
        $apiPref->data = 'abc';

        Preferences::shouldReceive('getForUser')
                   ->withArgs([Mockery::any(), 'fake_api_key', false])->once()
                   ->andReturn($apiPref);

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();
        $this->assertEquals(['api_key' => ''], $result);
    }

    /**
     * No API key in preference.
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testGetViewParametersDataNull(): void
    {
        // API key should be empty:
        $apiPref       = new Preference;
        $apiPref->data = null;

        Preferences::shouldReceive('getForUser')
                   ->withArgs([Mockery::any(), 'fake_api_key', false])->once()
                   ->andReturn($apiPref);

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();
        $this->assertEquals(['api_key' => ''], $result);
    }

    /**
     * Good API key length in preferences
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testGetViewParametersGoodLength(): void
    {
        // API key should be empty:
        $apiPref       = new Preference;
        $apiPref->data = '123456789012345678901234567890AA';

        Preferences::shouldReceive('getForUser')
                   ->withArgs([Mockery::any(), 'fake_api_key', false])->twice()
                   ->andReturn($apiPref);

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();
        $this->assertEquals(['api_key' => '123456789012345678901234567890AA'], $result);
    }

    /**
     * No preference at all.
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testGetViewParametersPrefNull(): void
    {
        Preferences::shouldReceive('getForUser')
                   ->withArgs([Mockery::any(), 'fake_api_key', false])->once()
                   ->andReturn(null);

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();
        $this->assertEquals(['api_key' => ''], $result);
    }

    /**
     * Also test hasApiKey but that one is covered.
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testIsComplete(): void
    {
        // API key should be empty:
        $apiPref       = new Preference;
        $apiPref->data = null;

        Preferences::shouldReceive('getForUser')
                   ->withArgs([Mockery::any(), 'fake_api_key', false])->once()
                   ->andReturn($apiPref);

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * Also test hasApiKey but that one is covered.
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testStorePrerequisitesBad(): void
    {
        $data   = [
            'api_key' => 'Hallo',
        ];
        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $messages = $object->storePrerequisites($data);
        $this->assertCount(1, $messages);
        $this->assertEquals('API key must be 32 chars.', $messages->first());
    }

    /**
     * Also test hasApiKey but that one is covered.
     *
     * @covers \FireflyIII\Import\Prerequisites\FakePrerequisites
     */
    public function testStorePrerequisitesGood(): void
    {
        $data = [
            'api_key' => '123456789012345678901234567890AA',
        ];

        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'fake_api_key', '123456789012345678901234567890AA'])->once();

        $object = new FakePrerequisites();
        $object->setUser($this->user());
        $messages = $object->storePrerequisites($data);
        $this->assertCount(0, $messages);
    }

}

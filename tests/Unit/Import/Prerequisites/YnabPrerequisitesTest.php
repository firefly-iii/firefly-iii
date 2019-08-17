<?php
/**
 * YnabPrerequisitesTest.php
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

namespace tests\Unit\Import\Prerequisites;

use FireflyIII\Import\Prerequisites\YnabPrerequisites;
use FireflyIII\Models\Preference;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class YnabPrerequisitesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class YnabPrerequisitesTest extends TestCase
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
     * @covers \FireflyIII\Import\Prerequisites\YnabPrerequisites
     */
    public function testGetView(): void
    {

        $object = new YnabPrerequisites;
        $object->setUser($this->user());
        $this->assertEquals('import.ynab.prerequisites', $object->getView());
    }

    /**
     * First test, user has empty.
     *
     * @covers \FireflyIII\Import\Prerequisites\YnabPrerequisites
     */
    public function testGetViewParametersEmpty(): void
    {
        $clientId       = new Preference;
        $clientId->data = '';

        $clientSecret       = new Preference;
        $clientSecret->data = '';

        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'ynab_client_id', null])->andReturn($clientId);
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'ynab_client_secret', null])->andReturn($clientSecret);

        $object = new YnabPrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();

        $expected = ['client_id' => '', 'client_secret' => '', 'callback_uri' => 'http://localhost/import/ynab-callback', 'is_https' => false];

        $this->assertEquals($expected, $result);
    }

    /**
     * First test, user has nothing.
     *
     * @covers \FireflyIII\Import\Prerequisites\YnabPrerequisites
     */
    public function testGetViewParametersFilled(): void
    {
        $clientId       = new Preference;
        $clientId->data = 'client-id';

        $clientSecret       = new Preference;
        $clientSecret->data = 'client-secret';

        Preferences::shouldReceive('getForUser')->twice()->withArgs([Mockery::any(), 'ynab_client_id', null])->andReturn($clientId);
        Preferences::shouldReceive('getForUser')->twice()->withArgs([Mockery::any(), 'ynab_client_secret', null])->andReturn($clientSecret);

        $object = new YnabPrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();

        $expected = ['client_id' => 'client-id', 'client_secret' => 'client-secret', 'callback_uri' => 'http://localhost/import/ynab-callback',
                     'is_https'  => false];

        $this->assertEquals($expected, $result);
    }

    /**
     * First test, user has nothing.
     *
     * @covers \FireflyIII\Import\Prerequisites\YnabPrerequisites
     */
    public function testGetViewParametersNull(): void
    {

        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'ynab_client_id', null])->andReturn(null);
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'ynab_client_secret', null])->andReturn(null);

        $object = new YnabPrerequisites();
        $object->setUser($this->user());
        $result = $object->getViewParameters();

        $expected = ['client_id' => '', 'client_secret' => '', 'callback_uri' => 'http://localhost/import/ynab-callback', 'is_https' => false];

        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \FireflyIII\Import\Prerequisites\YnabPrerequisites
     */
    public function testIsComplete(): void
    {

        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'ynab_client_id', null])->andReturn(null);

        $object = new YnabPrerequisites();
        $object->setUser($this->user());
        $result = $object->isComplete();

        $this->assertFalse($result);
    }

    /**
     *
     */
    public function testStorePrerequisites(): void
    {

        Preferences::shouldReceive('setForUser')->once()->withArgs([Mockery::any(), 'ynab_client_id', 'hello']);
        Preferences::shouldReceive('setForUser')->once()->withArgs([Mockery::any(), 'ynab_client_secret', 'hi there']);

        $data = [
            'client_id'     => 'hello',
            'client_secret' => 'hi there',
        ];

        $object = new YnabPrerequisites();
        $object->setUser($this->user());
        $object->storePrerequisites($data);
    }
}

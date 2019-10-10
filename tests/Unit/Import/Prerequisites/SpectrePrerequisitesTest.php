<?php
/**
 * SpectrePrerequisitesTest.php
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

namespace Tests\Unit\Import\Prerequisites;


use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Models\Preference;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class SpectrePrerequisitesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SpectrePrerequisitesTest extends TestCase
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
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testGetView(): void
    {

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertEquals('import.spectre.prerequisites', $object->getView());
    }

    /**
     * Returns NULL as much as possible, forcing system to generate new keys.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testGetViewParameters(): void
    {
        $publicKey       = new Preference;
        $publicKey->name = 'spectre_public_key';
        $publicKey->data = '---PUBKEY---';

        $privateKey       = new Preference;
        $privateKey->name = 'spectre_private_key';
        $privateKey->data = '---PRIVKEY---';

        // get secret
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_secret', null])
                   ->andReturnNull();

        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturnNull();

        // get users public key
        // second time it should exist.
        Preferences::shouldReceive('getForUser')->twice()
                   ->withArgs([Mockery::any(), 'spectre_public_key', null])
                   ->andReturn(null, $publicKey);
        // SET users new pulic key
        Preferences::shouldReceive('setForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_public_key', Mockery::any()])
                   ->andReturn($publicKey);
        // SET private key
        Preferences::shouldReceive('setForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_private_key', Mockery::any()])
                   ->andReturn($privateKey);


        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $return = $object->getViewParameters();
        $this->assertEquals(
            [
                'app_id'     => '',
                'secret'     => '',
                'public_key' => '---PUBKEY---',
            ], $return
        );
    }

    /**
     * App ID exists, secret is empty.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testIsComplete(): void
    {
        $appId       = new Preference;
        $appId->name = 'spectre_app_id';
        $appId->data = 'Some app id';

        $secret       = new Preference;
        $secret->name = 'spectre_secret';
        $secret->data = 'Hello';
        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturn($appId);

        // get secret
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_secret', null])
                   ->andReturn($secret);

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertTrue($object->isComplete());
    }

    /**
     * App ID exists, secret is null.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testIsCompleteAppId(): void
    {
        $appId       = new Preference;
        $appId->name = 'spectre_app_id';
        $appId->data = 'Some app id';
        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturn($appId);

        // get secret
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_secret', null])
                   ->andReturnNull();

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * App ID is "" and Secret is NULL. Since App ID is "" secret won't be polled.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testIsCompleteEmpty(): void
    {
        $appId       = new Preference;
        $appId->name = 'spectre_app_id';
        $appId->data = '';

        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturn($appId);

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * App ID and Secret are NULL. Since App ID is null secret won't be polled.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testIsCompleteNull(): void
    {
        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturnNull();

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     * App ID exists, secret is empty.
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testIsCompleteSecretEmpty(): void
    {
        $appId       = new Preference;
        $appId->name = 'spectre_app_id';
        $appId->data = 'Some app id';

        $secret       = new Preference;
        $secret->name = 'spectre_secret';
        $secret->data = '';
        // get App ID
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', null])
                   ->andReturn($appId);

        // get secret
        Preferences::shouldReceive('getForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_secret', null])
                   ->andReturn($secret);

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertFalse($object->isComplete());
    }

    /**
     *
     * @covers \FireflyIII\Import\Prerequisites\SpectrePrerequisites
     */
    public function testStorePrerequisites(): void
    {
        $data = [
            'app_id' => 'Some app ID',
            'secret' => 'Very secret!',
        ];
        // set values
        Preferences::shouldReceive('setForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_app_id', $data['app_id']])
                   ->andReturn(new Preference());
        Preferences::shouldReceive('setForUser')->once()
                   ->withArgs([Mockery::any(), 'spectre_secret', $data['secret']])
                   ->andReturn(new Preference());

        $object = new SpectrePrerequisites;
        $object->setUser($this->user());
        $this->assertEquals(0, $object->storePrerequisites($data)->count());
    }

}

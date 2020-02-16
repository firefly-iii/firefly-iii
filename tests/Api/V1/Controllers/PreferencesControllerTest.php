<?php
/**
 * PreferencesControllerTest.php
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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\PreferenceTransformer;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class PreferencesControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PreferencesControllerTest extends TestCase
{

    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PreferenceController
     */
    public function testUpdateArray(): void
    {
        $transformer  = $this->mock(PreferenceTransformer::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls to preferences facade.
        $pref       = new Preference;
        $pref->data = [1, 2, 3];

        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'frontPageAccounts', []])->andReturn($pref);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['frontPageAccounts', ['4', '5', '6']])->atLeast()->once()->andReturn($pref);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        ///** @var Preference $preference */
        //$preference = Preferences::setForUser($this->user(), 'frontPageAccounts', [1, 2, 3]);
        $data     = ['data' => '4,5,6'];
        $response = $this->put(route('api.v1.preferences.update', ['frontPageAccounts']), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PreferenceController
     */
    public function testUpdateBoolean(): void
    {
        $transformer  = $this->mock(PreferenceTransformer::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls to preferences facade.
        $pref            = new Preference;
        $pref->data      = false;
        $countable       = new Preference;
        $countable->data = [1];

        $saved          = new Preference;
        $saved->user_id = $this->user()->id;
        $saved->name    = 'twoFactorAuthEnabled';
        $saved->data    = false;
        $saved->save();

        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'frontPageAccounts', []])->andReturn($countable);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['twoFactorAuthEnabled', '1'])->atLeast()->once()->andReturn($pref);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        /** @var Preference $preference */
        $data     = ['data' => '1'];
        $response = $this->put(route('api.v1.preferences.update', ['twoFactorAuthEnabled']), $data, ['Accept' => 'application/json']);

        $response->assertStatus(200);


    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PreferenceController
     */
    public function testUpdateDefault(): void
    {
        $transformer  = $this->mock(PreferenceTransformer::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock calls to preferences facade.
        $pref            = new Preference;
        $pref->data      = 'EUR';
        $countable       = new Preference;
        $countable->data = [1];

        $saved          = new Preference;
        $saved->user_id = $this->user()->id;
        $saved->name    = 'twoFactorEnabled';
        $saved->data    = false;
        $saved->save();

        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'frontPageAccounts', []])->andReturn($countable);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['currencyPreference', 'EUR'])->atLeast()->once()->andReturn($pref);

        $data       = ['data' => 'EUR'];
        $response   = $this->put(route('api.v1.preferences.update', ['currencyPreference']), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\PreferenceController
     */
    public function testUpdateInteger(): void
    {
        $transformer  = $this->mock(PreferenceTransformer::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock calls to preferences facade.
        $pref            = new Preference;
        $pref->data      = 'EUR';
        $countable       = new Preference;
        $countable->data = [1];

        $saved          = new Preference;
        $saved->user_id = $this->user()->id;
        $saved->name    = 'listPageSize';
        $saved->data    = 200;
        $saved->save();

        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'frontPageAccounts', []])->andReturn($countable);
        Preferences::shouldReceive('set')->atLeast()->once()->withArgs(['listPageSize', '434'])->atLeast()->once()->andReturn($pref);

        $data       = ['data' => '434'];
        $response   = $this->put(route('api.v1.preferences.update', ['listPageSize']), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

}

<?php
declare(strict_types=1);
/**
 * CorrectDatabaseTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class CreateAccessTokensTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateAccessTokensTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\CreateAccessTokens
     */
    public function testHandle(): void
    {
        $users      = new Collection([$this->user()]);
        $repository = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('all')->atLeast()->once()->andReturn($users);

        // mock preferences thing:
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])
                   ->once()->andReturn(null);

        // null means user object will generate one and store it.
        Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(), 'access_token', Mockery::any()])
                   ->once();


        $this->artisan('firefly-iii:create-access-tokens')
             ->expectsOutput(sprintf('Generated access token for user %s', $this->user()->email))
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\CreateAccessTokens
     */
    public function testHandlePrefExists(): void
    {
        $users      = new Collection([$this->user()]);
        $repository = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('all')->atLeast()->once()->andReturn($users);

        // mock preferences thing:
        $preference       = new Preference;
        $preference->data = '123';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])
                   ->once()->andReturn($preference);

        // null means user object will generate one and store it.
        Preferences::shouldNotReceive('setForUser');

        $this->artisan('firefly-iii:create-access-tokens')
             ->expectsOutput('All access tokens OK!')
             ->assertExitCode(0);
    }
}

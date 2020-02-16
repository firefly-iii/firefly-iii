<?php
/**
 * HelpControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Models\Preference;
use Log;
use Tests\TestCase;

/**
 * Class HelpControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HelpControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\HelpController
     */
    public function testShow(): void
    {
        $this->mockDefaultSession();

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->andReturn(true)->once();
        $help->shouldReceive('inCache')->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->andReturn('Recent new content here.')->once();
        $help->shouldReceive('putInCache')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('Recent new content here.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController
     */
    public function testShowBackupFromCache(): void
    {
        $this->mockDefaultSession();

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->withArgs(['index'])->andReturn(true)->once();
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'en_US'])->andReturn('')->once();

        // is US in cache?
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(true)->once();
        $help->shouldReceive('getFromCache')->withArgs(['index', 'en_US'])->andReturn('US from cache.')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('US from cache.');

        // put English back:
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'en_US']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController
     */
    public function testShowBackupFromGithub(): void
    {
        $this->mockDefaultSession();

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->withArgs(['index'])->andReturn(true)->once();
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'en_US'])->andReturn('')->once();

        // is US in cache?
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'en_US'])->andReturn('')->once();

        $help->shouldReceive('putInCache')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('This help text is not yet available in your language');

        // put English back:
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'en_US']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController
     */
    public function testShowCached(): void
    {
        $this->mockDefaultSession();

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->andReturn(true)->once();
        $help->shouldReceive('inCache')->andReturn(true)->once();
        $help->shouldReceive('getFromCache')->andReturn('Cached help content here.')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('Cached help content here.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController
     */
    public function testShowNoRoute(): void
    {
        $this->mockDefaultSession();

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->andReturn(false)->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('There is no help for this route.');
    }
}

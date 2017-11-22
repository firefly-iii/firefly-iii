<?php
/**
 * HelpControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Models\Preference;
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
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     * @covers \FireflyIII\Http\Controllers\HelpController::getHelpText
     * @covers \FireflyIII\Http\Controllers\HelpController::__construct
     */
    public function testShow()
    {
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
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     * @covers \FireflyIII\Http\Controllers\HelpController::getHelpText
     */
    public function testShowBackupFromCache()
    {
        // force pref in dutch for test
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'nl_NL']);

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->withArgs(['index'])->andReturn(true)->once();
        $help->shouldReceive('inCache')->withArgs(['index', 'nl_NL'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'nl_NL'])->andReturn('')->once();

        // is US in cache?
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(true)->once();
        $help->shouldReceive('getFromCache')->withArgs(['index', 'en_US'])->andReturn('US from cache.')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('US from cache.'); // Dutch translation

        // put English back:
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'en_US']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     * @covers \FireflyIII\Http\Controllers\HelpController::getHelpText
     */
    public function testShowBackupFromGithub()
    {
        // force pref in dutch for test
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'nl_NL']);

        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->withArgs(['index'])->andReturn(true)->once();
        $help->shouldReceive('inCache')->withArgs(['index', 'nl_NL'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'nl_NL'])->andReturn('')->once();

        // is US in cache?
        $help->shouldReceive('inCache')->withArgs(['index', 'en_US'])->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->withArgs(['index', 'en_US'])->andReturn('')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('Er is geen hulptekst voor deze pagina.'); // Dutch

        // put English back:
        Preference::where('user_id', $this->user()->id)->where('name', 'language')->delete();
        Preference::create(['user_id' => $this->user()->id, 'name' => 'language', 'data' => 'en_US']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     * @covers \FireflyIII\Http\Controllers\HelpController::getHelpText
     */
    public function testShowCached()
    {
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
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     * @covers \FireflyIII\Http\Controllers\HelpController::getHelpText
     */
    public function testShowNoRoute()
    {
        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->andReturn(false)->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
        $response->assertSee('There is no help for this route.');
    }
}

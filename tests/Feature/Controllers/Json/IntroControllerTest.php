<?php
/**
 * IntroControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class IntroControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IntroControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\IntroController
     */
    public function testGetIntroSteps(): void
    {
        $this->mockDefaultSession();
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['index']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController
     */
    public function testGetIntroStepsAsset(): void
    {
        $this->mockDefaultSession();
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController
     */
    public function testGetIntroStepsOutro(): void
    {
        $this->mockDefaultSession();
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['reports_report', 'category']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController
     */
    public function testPostEnable(): void
    {
        $this->mockDefaultSession();

        Preferences::shouldReceive('set')->withArgs(['shown_demo_accounts_create_asset', false])->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('json.intro.enable', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController
     */
    public function testPostFinished(): void
    {
        $this->mockDefaultSession();

        Preferences::shouldReceive('set')->withArgs(['shown_demo_accounts_create_asset', true])->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('json.intro.finished', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

}

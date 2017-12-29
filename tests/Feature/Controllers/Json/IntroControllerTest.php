<?php
/**
 * IntroControllerTest.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;

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
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getIntroSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getBasicSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getSpecificSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::hasOutroStep
     */
    public function testGetIntroSteps()
    {
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['index']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getIntroSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getBasicSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getSpecificSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::hasOutroStep
     */
    public function testGetIntroStepsAsset()
    {
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getIntroSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getBasicSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::getSpecificSteps
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::hasOutroStep
     */
    public function testGetIntroStepsOutro()
    {
        $this->be($this->user());
        $response = $this->get(route('json.intro', ['reports_report', 'category']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::postEnable
     */
    public function testPostEnable()
    {
        $this->be($this->user());
        $response = $this->post(route('json.intro.enable', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\IntroController::postFinished
     */
    public function testPostFinished()
    {
        $this->be($this->user());
        $response = $this->post(route('json.intro.finished', ['accounts_create', 'asset']));
        $response->assertStatus(200);
    }

}

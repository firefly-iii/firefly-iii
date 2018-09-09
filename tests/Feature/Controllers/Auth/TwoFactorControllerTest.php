<?php
/**
 * TwoFactorControllerTest.php
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

namespace Tests\Feature\Controllers\Auth;

use FireflyIII\Models\Preference;
use Google2FA;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class TwoFactorControllerTest
 */
class TwoFactorControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testIndex(): void
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'JZMES376Z6YXY4QZ';
        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';

        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testIndexNo2FA(): void
    {
        $this->be($this->user());

        $falsePreference       = new Preference;
        $falsePreference->data = false;
        $langPreference        = new Preference;
        $langPreference->data  = 'en_US';

        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($falsePreference)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn(null)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn(null)->once();
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testIndexNoSecret(): void
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = '';
        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';

        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testLostTwoFactor(): void
    {
        $this->be($this->user());

        $truePreference         = new Preference;
        $truePreference->data   = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'JZMES376Z6YXY4QZ';
        $langPreference         = new Preference;
        $langPreference->data   = 'en_US';

        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference);
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference);

        $response = $this->get(route('two-factor.lost'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController
     */
    public function testPostIndex(): void
    {
        $data = ['code' => '123456'];
        Google2FA::shouldReceive('verifyKey')->andReturn(true)->once();
        $this->session(['remember_login' => true]);

        $this->be($this->user());
        $response = $this->post(route('two-factor.post'), $data);
        $response->assertStatus(302);
    }
}

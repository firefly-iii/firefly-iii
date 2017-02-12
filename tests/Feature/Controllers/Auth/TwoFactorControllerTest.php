<?php
/**
 * TwoFactorControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Auth;


use FireflyIII\Models\Preference;
use Preferences;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     */
    public function testIndex()
    {
        $this->be($this->user());

        $falsePreference        = new Preference;
        $falsePreference->data  = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'BlablaSeecret';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($falsePreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference);
        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::lostTwoFactor
     */
    public function testLostTwoFactor()
    {
        $this->be($this->user());

        $truePreference         = new Preference;
        $truePreference->data   = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'BlablaSeecret';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference);
        $response = $this->get(route('two-factor.lost'));
        $response->assertStatus(200);
    }

}
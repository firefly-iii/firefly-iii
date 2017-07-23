<?php
/**
 * TwoFactorControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;


use FireflyIII\Models\Preference;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Preferences;
use Tests\TestCase;

/**
 * Class TwoFactorControllerTest
 *
 * @package Tests\Feature\Controllers\Auth
 */
class TwoFactorControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     */
    public function testIndex()
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'BlablaSeecret';


        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     */
    public function testIndexNo2FA()
    {
        $this->be($this->user());

        $falsePreference       = new Preference;
        $falsePreference->data = false;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($falsePreference)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn(null)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn(null)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     * @expectedExceptionMessage Your two factor authentication secret is empty
     */
    public function testIndexNoSecret()
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = '';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(500);
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

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::postIndex
     */
    public function testPostIndex()
    {
        $data   = ['code' => '123456'];
        $google = $this->mock(Google2FA::class);
        $google->shouldReceive('verifyKey')->andReturn(true)->once();

        $this->be($this->user());
        $response = $this->post(route('two-factor.post'), $data);
        $response->assertStatus(302);
    }

}

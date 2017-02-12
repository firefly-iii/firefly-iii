<?php
/**
 * PreferencesControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class PreferencesControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::code
     */
    public function testCode()
    {
        $this->be($this->user());
        $response = $this->get(route('preferences.code'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::deleteCode
     */
    public function testDeleteCode()
    {
        $this->be($this->user());
        $response = $this->get(route('preferences.delete-code'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertRedirect(route('preferences.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('preferences.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::postIndex
     */
    public function testPostIndex()
    {
        $data = [
            'fiscalYearStart'       => '2016-01-01',
            'frontPageAccounts'     => [],
            'viewRange'             => '1M',
            'customFiscalYear'      => 0,
            'showDepositsFrontpage' => 0,
            'transactionPageSize'   => 100,
            'twoFactorAuthEnabled'  => 0,
            'language'              => 'en_US',
            'tj'                    => [],
        ];

        $this->be($this->user());
        $response = $this->post(route('preferences.update'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('preferences.index'));
    }

}
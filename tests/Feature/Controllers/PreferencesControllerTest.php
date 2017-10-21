<?php
/**
 * PreferencesControllerTest.php
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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Preferences;
use Tests\TestCase;

/**
 * Class PreferencesControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreferencesControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::code
     * @covers \FireflyIII\Http\Controllers\PreferencesController::getDomain
     */
    public function testCode()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $google       = $this->mock(Google2FA::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $google->shouldReceive('generateSecretKey')->andReturn('secret');
        $google->shouldReceive('getQRCodeInline')->andReturn('long-data-url');

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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('preferences.delete-code'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertRedirect(route('preferences.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::index
     * @covers \FireflyIII\Http\Controllers\PreferencesController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('preferences.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     *
     */
    public function testPostCode()
    {
        $secret = '0123456789abcde';
        $key    = '123456';
        $google = $this->mock(Google2FA::class);

        $this->withoutMiddleware();
        $this->session(['two-factor-secret' => $secret]);

        Preferences::shouldReceive('set')->withArgs(['twoFactorAuthEnabled', 1])->once();
        Preferences::shouldReceive('set')->withArgs(['twoFactorAuthSecret', $secret])->once();
        Preferences::shouldReceive('mark')->once();

        $google->shouldReceive('verifyKey')->withArgs([$secret, $key])->andReturn(true);

        $data = [
            'code' => $key,
        ];

        $this->be($this->user());
        $response = $this->post(route('preferences.code.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::postIndex
     */
    public function testPostIndex()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $userRepos->shouldReceive('hasRole')->andReturn(false);

        $data = [
            'fiscalYearStart'       => '2016-01-01',
            'frontPageAccounts'     => [1],
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

    /**
     * User wants 2FA and has secret already.
     *
     * @covers \FireflyIII\Http\Controllers\PreferencesController::postIndex
     */
    public function testPostIndexWith2FA()
    {
        $this->withoutMiddleware();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->andReturn(false);

        // mock preferences (in a useful way?)
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn('12345');
        Preferences::shouldReceive('set');
        Preferences::shouldReceive('mark');

        $data = [
            'fiscalYearStart'       => '2016-01-01',
            'frontPageAccounts'     => [1],
            'viewRange'             => '1M',
            'customFiscalYear'      => 0,
            'showDepositsFrontpage' => 0,
            'transactionPageSize'   => 100,
            'twoFactorAuthEnabled'  => 1,
            'language'              => 'en_US',
            'tj'                    => [],
        ];

        $this->be($this->user());
        $response = $this->post(route('preferences.update'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // go to code to get a secret.
        $response->assertRedirect(route('preferences.index'));
    }

    /**
     * User wants 2FA and has no secret.
     *
     * @covers \FireflyIII\Http\Controllers\PreferencesController::postIndex
     */
    public function testPostIndexWithEmpty2FA()
    {
        $this->withoutMiddleware();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->andReturn(false);

        // mock preferences (in a useful way?)
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn(null);
        Preferences::shouldReceive('set');
        Preferences::shouldReceive('mark');

        $data = [
            'fiscalYearStart'       => '2016-01-01',
            'frontPageAccounts'     => [1],
            'viewRange'             => '1M',
            'customFiscalYear'      => 0,
            'showDepositsFrontpage' => 0,
            'transactionPageSize'   => 100,
            'twoFactorAuthEnabled'  => 1,
            'language'              => 'en_US',
            'tj'                    => [],
        ];

        $this->be($this->user());
        $response = $this->post(route('preferences.update'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // go to code to get a secret.
        $response->assertRedirect(route('preferences.code'));
    }

}

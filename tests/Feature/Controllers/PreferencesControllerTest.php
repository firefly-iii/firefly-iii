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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Tests\TestCase;

/**
 * Class PreferencesControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class PreferencesControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\PreferencesController::code
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
     * @covers \FireflyIII\Http\Controllers\PreferencesController::postIndex
     */
    public function testPostIndex()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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

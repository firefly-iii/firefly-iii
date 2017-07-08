<?php
/**
 * NewUserControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Tests\TestCase;

/**
 * Class NewUserControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class NewUserControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::index
     * @covers \FireflyIII\Http\Controllers\NewUserController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(0);

        $this->be($this->emptyUser());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::index
     * @covers \FireflyIII\Http\Controllers\NewUserController::__construct
     */
    public function testIndexExisting()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(1);


        $this->be($this->user());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::submit
     * @covers \FireflyIII\Http\Controllers\NewUserController::createAssetAccount
     * @covers \FireflyIII\Http\Controllers\NewUserController::createSavingsAccount
     * @covers \FireflyIII\Http\Controllers\NewUserController::storeCreditCard
     */
    public function testSubmit()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);


        $data = [
            'bank_name'         => 'New bank',
            'savings_balance'   => '1000',
            'bank_balance'      => '100',
            'credit_card_limit' => '1000',
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::submit
     */
    public function testSubmitSingle()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->once();

        $data = [
            'bank_name'    => 'New bank',
            'bank_balance' => '100',
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}

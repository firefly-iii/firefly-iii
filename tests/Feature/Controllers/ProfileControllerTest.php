<?php
/**
 * ProfileControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Tests\TestCase;

/**
 * Class ProfileControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class ProfileControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::changePassword
     */
    public function testChangePassword()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.change-password'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::deleteAccount
     */
    public function testDeleteAccount()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.delete-account'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::index
     * @covers \FireflyIII\Http\Controllers\ProfileController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangePassword
     * @covers \FireflyIII\Http\Controllers\ProfileController::validatePassword
     */
    public function testPostChangePassword()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');

        $data = [
            'current_password'          => 'james',
            'new_password'              => 'james2',
            'new_password_confirmation' => 'james2',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.change-password.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangePassword
     * @covers \FireflyIII\Http\Controllers\ProfileController::validatePassword
     */
    public function testPostChangePasswordNotCorrect()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');

        $data = [
            'current_password'          => 'james3',
            'new_password'              => 'james2',
            'new_password_confirmation' => 'james2',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.change-password.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangePassword
     * @covers \FireflyIII\Http\Controllers\ProfileController::validatePassword
     */
    public function testPostChangePasswordSameNew()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');

        $data = [
            'current_password'          => 'james',
            'new_password'              => 'james',
            'new_password_confirmation' => 'james',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.change-password.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postDeleteAccount
     */
    public function testPostDeleteAccount()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('destroy')->once();
        $data = [
            'password' => 'james',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.delete-account.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postDeleteAccount
     */
    public function testPostDeleteAccountWrong()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $data = [
            'password' => 'james2',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.delete-account.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('profile.delete-account'));
        $response->assertSessionHas('error');
    }

}

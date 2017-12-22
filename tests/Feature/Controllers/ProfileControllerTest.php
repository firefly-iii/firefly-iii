<?php
/**
 * ProfileControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Preferences;
use Tests\TestCase;

/**
 * Class ProfileControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProfileControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::changeEmail()
     */
    public function testChangeEmail()
    {
        $this->be($this->user());
        $response = $this->get(route('profile.change-email'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

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
     * @covers                   \FireflyIII\Http\Controllers\ProfileController::confirmEmailChange()
     * @expectedExceptionMessage Invalid token
     */
    public function testConfirmEmailChangeNoToken()
    {
        Preferences::shouldReceive('findByName')->withArgs(['email_change_confirm_token'])->andReturn(new Collection());
        // email_change_confirm_token
        $response = $this->get(route('profile.confirm-email-change', ['some-fake-token']));
        $response->assertStatus(500);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController::confirmEmailChange()
     */
    public function testConfirmEmailWithToken()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('unblockUser');
        $preference       = new Preference;
        $preference->data = 'existing-token';
        /** @var \stdClass $preference */
        $preference->user = $this->user();
        Preferences::shouldReceive('findByName')->withArgs(['email_change_confirm_token'])->andReturn(new Collection([$preference]));
        // email_change_confirm_token
        $response = $this->get(route('profile.confirm-email-change', ['existing-token']));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
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
     * @throws \Exception
     */
    public function testIndex()
    {
        // delete access token.
        Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->delete();
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangeEmail
     */
    public function testPostChangeEmail()
    {
        $data       = [
            'email' => 'new@example.com',
        ];
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn(null);
        $repository->shouldReceive('changeEmail')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('profile.change-email.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangeEmail
     */
    public function testPostChangeEmailExisting()
    {
        $data       = [
            'email' => 'existing@example.com',
        ];
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn(new User);

        $this->be($this->user());
        $response = $this->post(route('profile.change-email.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangeEmail
     */
    public function testPostChangeEmailSame()
    {
        $data = [
            'email' => $this->user()->email,
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.change-email.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $response->assertRedirect(route('profile.change-email'));
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

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::regenerate()
     */
    public function testRegenerate()
    {
        $token        = '';
        $currentToken = Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->first();
        if (!is_null($currentToken)) {
            $token = $currentToken->data;
        }
        $this->be($this->user());
        $response = $this->post(route('profile.regenerate'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('profile.index'));

        $newToken = Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->first();
        $this->assertNotEquals($newToken->data, $token);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::undoEmailChange()
     */
    public function testUndoEmailChange()
    {
        $hash                  = hash('sha256', 'previous@example.com');
        $tokenPreference       = new Preference;
        $tokenPreference->data = 'token';
        /** @var \stdClass $tokenPreference */
        $tokenPreference->user = $this->user();

        $hashPreference       = new Preference;
        $hashPreference->data = 'previous@example.com';
        /** @var \stdClass $hashPreference */
        $hashPreference->user = $this->user();

        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection([$tokenPreference]));
        Preferences::shouldReceive('beginsWith')->once()->andReturn(new Collection([$hashPreference]));

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changeEmail')->once();
        $repository->shouldReceive('unblockUser')->once();

        $response = $this->get(route('profile.undo-email-change', ['token', $hash]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('login'));
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController::undoEmailChange()
     * @expectedExceptionMessage Invalid token
     */
    public function testUndoEmailChangeBadHash()
    {
        $hash                  = hash('sha256', 'previous@example.comX');
        $tokenPreference       = new Preference;
        $tokenPreference->data = 'token';
        /** @var \stdClass $tokenPreference */
        $tokenPreference->user = $this->user();

        $hashPreference       = new Preference;
        $hashPreference->data = 'previous@example.com';
        /** @var \stdClass $hashPreference */
        $hashPreference->user = $this->user();

        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection([$tokenPreference]));
        Preferences::shouldReceive('beginsWith')->once()->andReturn(new Collection([$hashPreference]));

        $response = $this->get(route('profile.undo-email-change', ['token', $hash]));
        $response->assertStatus(500);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController::undoEmailChange()
     * @expectedExceptionMessage Invalid token
     */
    public function testUndoEmailChangeBadToken()
    {
        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection);

        $response = $this->get(route('profile.undo-email-change', ['token', 'some-hash']));
        $response->assertStatus(500);
    }


}

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
use Google2FA;
use Illuminate\Support\Collection;
use Log;
use Mockery;
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
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testChangeEmail(): void
    {
        $this->be($this->user());
        $response = $this->get(route('profile.change-email'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testChangePassword(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.change-password'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testCode(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        Google2FA::shouldReceive('generateSecretKey')->andReturn('secret');
        Google2FA::shouldReceive('getQRCodeInline')->andReturn('long-data-url');

        $this->be($this->user());
        $response = $this->get(route('profile.code'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController
     * @expectedExceptionMessage Invalid token
     */
    public function testConfirmEmailChangeNoToken(): void
    {
        Preferences::shouldReceive('findByName')->withArgs(['email_change_confirm_token'])->andReturn(new Collection());
        // email_change_confirm_token
        $response = $this->get(route('profile.confirm-email-change', ['some-fake-token']));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testConfirmEmailWithToken(): void
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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testDeleteAccount(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.delete-account'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testDeleteCode(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.delete-code'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertRedirect(route('profile.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testEnable2FANoSecret(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->times(1)->andReturn(false);

        // ask about language:
        $langPreference       = new Preference;
        $langPreference->data = 'en_US';
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference)->times(2);

        // ask about twoFactorAuthEnabled
        $truePref       = new Preference;
        $truePref->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->times(1);

        // ask about range
        $rangePref       = new Preference;
        $rangePref->data = '1M';
        Preferences::shouldReceive('get')->withArgs(['viewRange', '1M'])->andReturn($rangePref)->once();

        // ask about list length:
        $listPref       = new Preference;
        $listPref->data = '50';
        Preferences::shouldReceive('get')->withArgs(['list-length', '10'])->andReturn($listPref)->once();


        // ask about currency
        $currencyPref       = new Preference;
        $currencyPref->data = 'EUR';
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($currencyPref);
        Preferences::shouldReceive('lastActivity')->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->twice()->andReturnNull();

        $this->be($this->user());
        $response = $this->post(route('profile.enable2FA'));
        $response->assertStatus(302);
        $response->assertRedirect(route('profile.code'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testEnable2FASecret(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->times(1)->andReturn(false);

        // ask about language:
        $langPreference       = new Preference;
        $langPreference->data = 'en_US';
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($langPreference)->times(2);

        // ask about twoFactorAuthEnabled
        $truePref       = new Preference;
        $truePref->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->times(1);

        // ask about range
        $rangePref       = new Preference;
        $rangePref->data = '1M';
        Preferences::shouldReceive('get')->withArgs(['viewRange', '1M'])->andReturn($rangePref)->once();

        // ask about list length:
        $listPref       = new Preference;
        $listPref->data = '50';
        Preferences::shouldReceive('get')->withArgs(['list-length', '10'])->andReturn($listPref)->once();

        $secretPref       = new Preference;
        $secretPref->data = 'X';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->twice()->andReturn(null, $secretPref);

        // set pref
        Preferences::shouldReceive('set')->once()->withArgs(['twoFactorAuthEnabled', 1]);


        // ask about currency
        $currencyPref       = new Preference;
        $currencyPref->data = 'EUR';
        Preferences::shouldReceive('getForUser')->once()->withArgs([Mockery::any(), 'currencyPreference', 'EUR'])->andReturn($currencyPref);
        Preferences::shouldReceive('lastActivity')->once();


        $this->be($this->user());
        $response = $this->post(route('profile.enable2FA'));
        $response->assertStatus(302);
        $response->assertRedirect(route('profile.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testIndex(): void
    {
        // delete access token.
        Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->delete();
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangeEmail(): void
    {
        $data       = [
            'email' => 'new@example.com',
        ];
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn(null);
        $repository->shouldReceive('changeEmail')->once()->andReturn(true);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('profile.change-email.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangeEmailExisting(): void
    {

        $data       = [
            'email' => 'existing@example.com',
        ];
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->once()->andReturn(new User);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('profile.change-email.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangeEmailSame(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangePassword(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangePasswordNotCorrect(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostChangePasswordSameNew(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostCode(): void
    {
        $secret = '0123456789abcde';
        $key    = '123456';

        $this->withoutMiddleware();
        $this->session(['two-factor-secret' => $secret]);

        Preferences::shouldReceive('set')->withArgs(['twoFactorAuthEnabled', 1])->once();
        Preferences::shouldReceive('set')->withArgs(['twoFactorAuthSecret', $secret])->once();
        Preferences::shouldReceive('mark')->once();

        Google2FA::shouldReceive('verifyKey')->withArgs([$secret, $key])->andReturn(true);

        $data = [
            'code' => $key,
        ];

        $this->be($this->user());
        $response = $this->post(route('profile.code.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostDeleteAccount(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('destroy')->once();
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $data = [
            'password' => 'james',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.delete-account.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testPostDeleteAccountWrong(): void
    {
        // mock stuff
        $repository   = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
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
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testRegenerate(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $token        = '';
        $currentToken = Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->first();
        if (null !== $currentToken) {
            $token = $currentToken->data;
        }
        $this->be($this->user());
        $response = $this->post(route('profile.regenerate'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('profile.index'));

        $newToken = Preference::where('user_id', $this->user()->id)->where('name', 'access_token')->first();
        $this->assertNotEquals($newToken->data, $token);

        // reset token for later test:
        $newToken->data = 'token';
        $newToken->save();

    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testUndoEmailChange(): void
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
     * @covers                   \FireflyIII\Http\Controllers\ProfileController
     * @expectedExceptionMessage Invalid token
     */
    public function testUndoEmailChangeBadHash(): void
    {
        $repository            = $this->mock(UserRepositoryInterface::class);
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
     * @covers                   \FireflyIII\Http\Controllers\ProfileController
     * @expectedExceptionMessage Invalid token
     */
    public function testUndoEmailChangeBadToken(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection);

        $response = $this->get(route('profile.undo-email-change', ['token', 'some-hash']));
        $response->assertStatus(500);
    }


}

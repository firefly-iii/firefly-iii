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

use Amount;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Google2FA;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use stdClass;
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
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testChangeEmail(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

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
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

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
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        Google2FA::shouldReceive('generateSecretKey')->andReturn('secret');
        Google2FA::shouldReceive('getQRCodeInline')->andReturn('long-data-url');

        $this->be($this->user());
        $response = $this->get(route('profile.code'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController
     */
    public function testConfirmEmailChangeNoToken(): void
    {
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);

        Preferences::shouldReceive('findByName')->withArgs(['email_change_confirm_token'])->andReturn(new Collection());

        Log::warning('The following error is part of a test.');
        $response = $this->get(route('profile.confirm-email-change', ['some-fake-token']));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testConfirmEmailWithToken(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(UserRepositoryInterface::class);

        $repository->shouldReceive('unblockUser');
        $preference       = new Preference;
        $preference->data = 'existing-token';
        /** @var stdClass $preference */
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
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

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
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        $userRepos->shouldReceive('setMFACode')->withArgs([Mockery::any(), null])->atLeast()->once();


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
        $this->mockDefaultSession();
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->times(1)->andReturn(false);

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
        //$this->mockDefaultSession(); // DISABLED ON PURPOSE
        $this->mockDefaultConfiguration();
        $repository = $this->mock(UserRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->andReturnNull();

        $euro       = $this->getEuro();

        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->times(1)->andReturn(false);

        $view       = new Preference;
        $view->data = '1M';
        Preferences::shouldReceive('get')->withArgs(['viewRange', Mockery::any()])->andReturn($view)->atLeast()->once();

        $lang       = new Preference;
        $lang->data = 'en_US';
        Preferences::shouldReceive('get')->withArgs(['language', 'en_US'])->andReturn($lang)->atLeast()->once();

        $list       = new Preference;
        $list->data = 50;
        Preferences::shouldReceive('get')->withArgs(['list-length', 10])->andReturn($list)->atLeast()->once();

        Amount::shouldReceive('getDefaultCurrency')->atLeast()->once()->andReturn($euro);

        $this->session(['rule-groups.delete.uri' => 'http://localhost']);

        $this->be($this->user());
        $response = $this->post(route('profile.enable2FA'));
        $response->assertStatus(302);
        $response->assertRedirect(route('profile.code'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        $pref       = new Preference;
        $pref->data = 'token';



        Preferences::shouldReceive('get')->withArgs(['access_token', null])->atLeast()->once()->andReturn($pref);

        $arrayPref = new Preference;
        $arrayPref->data = [];
        Preferences::shouldReceive('get')->withArgs(['mfa_recovery', []])->atLeast()->once()->andReturn($arrayPref);
        Preferences::shouldReceive('getForUser')->withArgs(['xxx'])->andReturn($pref);

        $this->be($this->user());
        $response = $this->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testIndexEmptyToken(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $pref       = new Preference;
        $pref->data = 'token';

        Preferences::shouldReceive('get')->withArgs(['access_token', null])->atLeast()->once()->andReturnNull();
        Preferences::shouldReceive('set')->withArgs(['access_token', Mockery::any()])->atLeast()->once()->andReturn($pref);

        $arrayPref = new Preference;
        $arrayPref->data = [];
        Preferences::shouldReceive('get')->withArgs(['mfa_recovery', []])->atLeast()->once()->andReturn($arrayPref);

        Preferences::shouldReceive('getForUser')->withArgs(['xxx'])->andReturn($pref);

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
        $this->mockDefaultSession();
        $data       = [
            'email' => 'new@example.com',
        ];
        $repository = $this->mock(UserRepositoryInterface::class);

        $repository->shouldReceive('findByEmail')->once()->andReturn(null);
        $repository->shouldReceive('changeEmail')->once()->andReturn(true);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

        $pref       = new Preference;
        $pref->data = 'invalid';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'email_change_confirm_token', 'invalid'])->andReturn($pref);
        $pref       = new Preference;
        $pref->data = 'invalid';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'email_change_undo_token', 'invalid'])->andReturn($pref);

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
        $this->mockDefaultSession();
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
        $this->mockDefaultSession();
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
        $this->mockDefaultSession();
        // mock stuff

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
        $this->mockDefaultSession();
        // mock stuff

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
        $this->mockDefaultSession();
        // mock stuff

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
        $userRepos = $this->mock(UserRepositoryInterface::class);
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();


        $secret = '0123456789abcde';
        $key    = '123456';

        $this->withoutMiddleware();
        $this->session(['two-factor-secret' => $secret]);

        $userRepos->shouldReceive('setMFACode')->withArgs([Mockery::any(), $secret])->atLeast()->once();

        // set recovery history
        Preferences::shouldReceive('set')->withArgs(['mfa_history', Mockery::any()])->atLeast()->once();

        // set recovery codes.
        Preferences::shouldReceive('set')->withArgs(['mfa_recovery', null])->atLeast()->once();

        $pref = new Preference;
        $pref->data=  [];
        Preferences::shouldReceive('get')->withArgs(['mfa_history', []])->atLeast()->once()->andReturn($pref);

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
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        // mock stuff

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
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(UserRepositoryInterface::class);
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
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

        Preferences::shouldReceive('set')->withArgs(['access_token', Mockery::any()])->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('profile.regenerate'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('profile.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController
     */
    public function testUndoEmailChange(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $hash                  = hash('sha256', 'previous@example.com');
        $tokenPreference       = new Preference;
        $tokenPreference->data = 'token';
        /** @var stdClass $tokenPreference */
        $tokenPreference->user = $this->user();

        $hashPreference       = new Preference;
        $hashPreference->data = 'previous@example.com';
        /** @var stdClass $hashPreference */
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
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        $hash                  = hash('sha256', 'previous@example.comX');
        $tokenPreference       = new Preference;
        $tokenPreference->data = 'token';
        /** @var stdClass $tokenPreference */
        $tokenPreference->user = $this->user();

        $hashPreference       = new Preference;
        $hashPreference->data = 'previous@example.com';
        /** @var stdClass $hashPreference */
        $hashPreference->user = $this->user();

        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection([$tokenPreference]));
        Preferences::shouldReceive('beginsWith')->once()->andReturn(new Collection([$hashPreference]));

        Log::warning('The following error is part of a test.');
        $response = $this->get(route('profile.undo-email-change', ['token', $hash]));
        $response->assertStatus(500);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ProfileController
     */
    public function testUndoEmailChangeBadToken(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(UserRepositoryInterface::class);
        Preferences::shouldReceive('findByName')->once()->andReturn(new Collection);

        $response = $this->get(route('profile.undo-email-change', ['token', 'some-hash']));
        $response->assertStatus(500);
    }


}

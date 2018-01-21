<?php
/**
 * UserEventHandlerTest.php
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

namespace Tests\Unit\Handlers\Events;

use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Handlers\Events\UserEventHandler;
use FireflyIII\Mail\ConfirmEmailChangeMail;
use FireflyIII\Mail\RegisteredUser as RegisteredUserMail;
use FireflyIII\Mail\RequestedNewPassword as RequestedNewPasswordMail;
use FireflyIII\Mail\UndoEmailChangeMail;
use FireflyIII\Models\Role;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Class UserEventHandlerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserEventHandlerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::attachUserRole
     * @covers \FireflyIII\Events\RegisteredUser
     */
    public function testAttachUserRole()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->emptyUser();
        $event      = new RegisteredUser($user, '127.0.0.1');

        $repository->shouldReceive('count')->andReturn(1)->once();
        $repository->shouldReceive('attachRole')->withArgs([$user, 'owner'])->andReturn(true)->once();
        $listener = new UserEventHandler();
        $listener->attachUserRole($event);
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::checkSingleUserIsAdmin
     */
    public function testCheckSingleUserIsAdminMulti()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->user();
        $event      = new Login($user, true);
        $listener   = new UserEventHandler();

        // mock stuff
        $repository->shouldReceive('count')->once()->andReturn(2);


        $listener->checkSingleUserIsAdmin($event);
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::checkSingleUserIsAdmin
     */
    public function testCheckSingleUserIsAdminNoRole()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->emptyUser();
        $event      = new Login($user, true);
        $listener   = new UserEventHandler();

        // mock stuff
        $repository->shouldReceive('count')->once()->andReturn(1);
        $repository->shouldReceive('getRole')->once()->andReturn(null);
        $repository->shouldReceive('attachRole')->once()->withArgs([Mockery::any(), 'owner']);
        $repository->shouldReceive('createRole')->once()->withArgs(['owner', 'Site Owner', 'User runs this instance of FF3'])->andReturn(new Role);

        $listener->checkSingleUserIsAdmin($event);
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::checkSingleUserIsAdmin
     */
    public function testCheckSingleUserIsAdminNotAdmin()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->emptyUser();
        $event      = new Login($user, true);
        $listener   = new UserEventHandler();

        // mock stuff
        $repository->shouldReceive('count')->once()->andReturn(1);
        $repository->shouldReceive('getRole')->once()->andReturn(new Role);
        $repository->shouldReceive('attachRole')->once()->withArgs([Mockery::any(), 'owner']);

        $listener->checkSingleUserIsAdmin($event);
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::checkSingleUserIsAdmin
     */
    public function testCheckSingleUserIsAdminSingle()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->user();
        $event      = new Login($user, true);
        $listener   = new UserEventHandler();

        // mock stuff
        $repository->shouldReceive('count')->once()->andReturn(1);

        $listener->checkSingleUserIsAdmin($event);
        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::sendEmailChangeConfirmMail
     * @covers \FireflyIII\Events\UserChangedEmail
     */
    public function testSendEmailChangeConfirmMail()
    {
        Mail::fake();
        $user     = $this->emptyUser();
        $event    = new UserChangedEmail($user, 'new@new', 'old@old', '127.0.0.1');
        $listener = new UserEventHandler;
        $listener->sendEmailChangeConfirmMail($event);

        // must send user an email:

        Mail::assertSent(
            ConfirmEmailChangeMail::class, function ($mail) {
            return $mail->hasTo('new@new') && '127.0.0.1' === $mail->ipAddress;
        }
        );

        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::sendEmailChangeUndoMail
     * @covers \FireflyIII\Events\UserChangedEmail
     */
    public function testSendEmailChangeUndoMail()
    {
        Mail::fake();
        $user     = $this->emptyUser();
        $event    = new UserChangedEmail($user, 'new@new', 'old@old', '127.0.0.1');
        $listener = new UserEventHandler;
        $listener->sendEmailChangeUndoMail($event);

        // must send user an email:

        Mail::assertSent(
            UndoEmailChangeMail::class, function ($mail) {
            return $mail->hasTo('old@old') && '127.0.0.1' === $mail->ipAddress;
        }
        );

        $this->assertTrue(true);
    }


    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::sendNewPassword
     * @covers \FireflyIII\Events\RequestedNewPassword
     */
    public function testSendNewPassword()
    {
        Mail::fake();
        $user     = $this->emptyUser();
        $event    = new RequestedNewPassword($user, 'token', '127.0.0.1');
        $listener = new UserEventHandler;
        $listener->sendNewPassword($event);

        // must send user an email:

        Mail::assertSent(
            RequestedNewPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && '127.0.0.1' === $mail->ipAddress;
        }
        );

        $this->assertTrue(true);
    }

    /**
     * @covers \FireflyIII\Handlers\Events\UserEventHandler::sendRegistrationMail
     * @covers \FireflyIII\Events\RegisteredUser
     */
    public function testSendRegistrationMail()
    {
        Mail::fake();
        $user  = $this->emptyUser();
        $event = new RegisteredUser($user, '127.0.0.1');

        $listener = new UserEventHandler;
        $listener->sendRegistrationMail($event);

        // must send user an email:
        Mail::assertSent(
            RegisteredUserMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && '127.0.0.1' === $mail->ipAddress;
        }
        );

        $this->assertTrue(true);
    }
}

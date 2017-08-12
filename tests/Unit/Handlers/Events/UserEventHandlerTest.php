<?php
/**
 * UserEventHandlerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\Handlers\Events;


use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Handlers\Events\UserEventHandler;
use FireflyIII\Mail\RegisteredUser as RegisteredUserMail;
use FireflyIII\Mail\RequestedNewPassword as RequestedNewPasswordMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Class UserEventHandlerTest
 *
 * @package Tests\Unit\Handlers\Events
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
            return $mail->hasTo($user->email) && $mail->ipAddress === '127.0.0.1';
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
            return $mail->hasTo($user->email) && $mail->ipAddress === '127.0.0.1';
        }
        );

        $this->assertTrue(true);
    }

}

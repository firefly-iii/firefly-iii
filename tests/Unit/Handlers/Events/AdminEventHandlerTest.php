<?php
/**
 * AdminEventHandlerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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


use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Handlers\Events\AdminEventHandler;
use FireflyIII\Mail\AdminTestMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class AdminEventHandlerTest
 */
class AdminEventHandlerTest extends TestCase
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
     * @covers \FireflyIII\Handlers\Events\AdminEventHandler
     * @covers \FireflyIII\Events\AdminRequestedTestMessage
     */
    public function testSendNoMessage(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $event      = new AdminRequestedTestMessage($this->user(), '127.0.0.1');


        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(false)->once();

        $listener = new AdminEventHandler();
        $this->assertTrue($listener->sendTestMessage($event));
    }

    /**
     * @covers \FireflyIII\Handlers\Events\AdminEventHandler
     * @covers \FireflyIII\Events\AdminRequestedTestMessage
     */
    public function testSendTestMessage(): void
    {
        Mail::fake();
        $repository = $this->mock(UserRepositoryInterface::class);
        $event      = new AdminRequestedTestMessage($this->user(), '127.0.0.1');


        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->once();

        $listener = new AdminEventHandler();
        $this->assertTrue($listener->sendTestMessage($event));

        // assert a message was sent.
        Mail::assertSent(
            AdminTestMail::class, function ($mail) {
            return $mail->hasTo('thegrumpydictator@gmail.com') && '127.0.0.1' === $mail->ipAddress;
        }
        );


    }
}

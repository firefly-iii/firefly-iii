<?php
/**
 * APIEventHandlerTest.php
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


use FireflyIII\Handlers\Events\APIEventHandler;
use FireflyIII\Mail\AccessTokenCreatedMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Events\AccessTokenCreated;
use Log;
use Tests\TestCase;

/**
 *
 * Class APIEventHandlerTest
 */
class APIEventHandlerTest extends TestCase
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
     * @covers \FireflyIII\Handlers\Events\APIEventHandler
     */
    public function testAccessTokenCreated(): void
    {
        Mail::fake();
        // mock objects.
        $repository = $this->mock(UserRepositoryInterface::class);

        // mock calls.
        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($this->user())->once();


        $event   = new AccessTokenCreated('1', '1', '1');
        $handler = new APIEventHandler;
        $handler->accessTokenCreated($event);

        // assert a message was sent.
        Mail::assertSent(
            AccessTokenCreatedMail::class, function ($mail) {
            return $mail->hasTo('thegrumpydictator@gmail.com') && '127.0.0.1' === $mail->ipAddress;
        }
        );

    }

}

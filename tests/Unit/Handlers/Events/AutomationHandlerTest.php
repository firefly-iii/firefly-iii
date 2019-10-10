<?php
/**
 * AutomationHandlerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Handlers\Events;


use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Handlers\Events\AutomationHandler;
use FireflyIII\Mail\ReportNewJournalsMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Log;
use stdClass;
use Tests\TestCase;

/**
 *
 * Class AutomationHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AutomationHandlerTest extends TestCase
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
     * @covers \FireflyIII\Handlers\Events\AutomationHandler
     * @covers \FireflyIII\Events\RequestedReportOnJournals
     */
    public function testReportJournals(): void
    {
        Mail::fake();
        // mock repositories
        $repository = $this->mock(UserRepositoryInterface::class);
        $journals   = new Collection;
        $journals->push(new stdClass);

        // mock calls.
        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($this->user())->once();

        $event   = new RequestedReportOnJournals(1, $journals);
        $handler = new AutomationHandler();

        $handler->reportJournals($event);

        // assert a message was sent.
        Mail::assertSent(
            ReportNewJournalsMail::class, function ($mail) {
            return $mail->hasTo('thegrumpydictator@gmail.com') && '127.0.0.1' === $mail->ipAddress;
        }
        );
    }
}

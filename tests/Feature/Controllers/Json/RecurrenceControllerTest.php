<?php
/**
 * RecurrenceControllerTest.php
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

namespace Tests\Feature\Controllers\Json;


use Carbon\Carbon;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 *
 * Class RecurrenceControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurrenceControllerTest extends TestCase
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
     * Ndom test
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsNdom(): void
    {
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();
        // collection of dates:
        $dates = [new Carbon('2018-01-01'), new Carbon('2018-01-07')];
        $repository->shouldReceive('getOccurrencesInRange')->withAnyArgs()->once()
                   ->andReturn($dates);

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-01-01',
            'ends'       => 'forever',
            'type'       => 'ndom,1,1', // weekly on Monday
            'reps'       => 0,
            'weekend'    => RecurrenceRepetition::WEEKEND_DO_NOTHING,
            'skip'       => 0,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        // expected data:
        $expected = [
            [
                'id'        => 'ndom20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-01',
                'end'       => '2018-01-01',
                'editable'  => false,
                'rendering' => 'background',
            ],
            [
                'id'        => 'ndom20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-07',
                'end'       => '2018-01-07',
                'editable'  => false,
                'rendering' => 'background',
            ],
        ];

        $response->assertExactJson($expected);
    }

    /**
     * yearly, until date
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsNumberOfEvents(): void
    {
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        // collection of dates:
        $dates = [new Carbon('2018-01-01'), new Carbon('2018-01-07')];
        $repository->shouldReceive('getXOccurrences')->withAnyArgs()->once()
                   ->andReturn($dates);

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-01-01',
            'ends'       => 'times',
            'type'       => 'yearly,1,1', // weekly on Monday
            'reps'       => 0,
            'weekend'    => RecurrenceRepetition::WEEKEND_DO_NOTHING,
            'skip'       => 0,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        // expected data:
        $expected = [
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-01',
                'end'       => '2018-01-01',
                'editable'  => false,
                'rendering' => 'background',
            ],
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-07',
                'end'       => '2018-01-07',
                'editable'  => false,
                'rendering' => 'background',
            ],
        ];

        $response->assertExactJson($expected);
    }

    /**
     * First date is after range, so nothing happens.
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsStartAfterEnd(): void
    {
        $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-02-01',
            'ends'       => '',
            'type'       => 'daily,',
            'reps'       => 1,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    /**
     * yearly, until date
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsUntilDate(): void
    {
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        // collection of dates:
        $dates = [new Carbon('2018-01-01'), new Carbon('2018-01-07')];
        $repository->shouldReceive('getOccurrencesInRange')->withAnyArgs()->once()
                   ->andReturn($dates);

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-01-01',
            'ends'       => 'until_date',
            'type'       => 'yearly,1,1', // weekly on Monday
            'reps'       => 0,
            'weekend'    => RecurrenceRepetition::WEEKEND_DO_NOTHING,
            'skip'       => 0,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        // expected data:
        $expected = [
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-01',
                'end'       => '2018-01-01',
                'editable'  => false,
                'rendering' => 'background',
            ],
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-07',
                'end'       => '2018-01-07',
                'editable'  => false,
                'rendering' => 'background',
            ],
        ];

        $response->assertExactJson($expected);
    }

    /**
     * Every week on Monday.
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsWeeklyMonday(): void
    {
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        // collection of dates:
        $dates = [new Carbon('2018-01-01'), new Carbon('2018-01-07')];
        $repository->shouldReceive('getOccurrencesInRange')->withAnyArgs()->once()
                   ->andReturn($dates);

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-01-01',
            'ends'       => 'forever',
            'type'       => 'weekly,1', // weekly on Monday
            'reps'       => 0,
            'weekend'    => RecurrenceRepetition::WEEKEND_DO_NOTHING,
            'skip'       => 0,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        // expected data:
        $expected = [
            [
                'id'        => 'weekly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-01',
                'end'       => '2018-01-01',
                'editable'  => false,
                'rendering' => 'background',
            ],
            [
                'id'        => 'weekly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-07',
                'end'       => '2018-01-07',
                'editable'  => false,
                'rendering' => 'background',
            ],
        ];

        $response->assertExactJson($expected);
    }

    /**
     * yearly
     *
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testEventsYearly(): void
    {
        $repository = $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        // collection of dates:
        $dates = [new Carbon('2018-01-01'), new Carbon('2018-01-07')];
        $repository->shouldReceive('getOccurrencesInRange')->withAnyArgs()->once()
                   ->andReturn($dates);

        $parameters = [
            'start'      => '2018-01-01',
            'end'        => '2018-01-31',
            'first_date' => '2018-01-01',
            'ends'       => 'forever',
            'type'       => 'yearly,1,1', // weekly on Monday
            'reps'       => 0,
            'weekend'    => RecurrenceRepetition::WEEKEND_DO_NOTHING,
            'skip'       => 0,
        ];
        $this->be($this->user());
        $response = $this->get(route('recurring.events') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        // expected data:
        $expected = [
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-01',
                'end'       => '2018-01-01',
                'editable'  => false,
                'rendering' => 'background',
            ],
            [
                'id'        => 'yearly20180101',
                'title'     => 'X',
                'allDay'    => true,
                'start'     => '2018-01-07',
                'end'       => '2018-01-07',
                'editable'  => false,
                'rendering' => 'background',
            ],
        ];

        $response->assertExactJson($expected);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\RecurrenceController
     */
    public function testSuggest(): void
    {
        $this->mock(RecurringRepositoryInterface::class);
        $this->mockDefaultSession();

        $this->be($this->user());

        $parameters = [
            'past'       => 'true',
            'pre_select' => 'daily',
            'date'       => '2018-01-01',
        ];

        $response = $this->get(route('recurring.suggest') . '?' . http_build_query($parameters));
        $response->assertStatus(200);

        $expected = [
            'daily'             => ['label' => 'Every day', 'selected' => true],
            'monthly,1'         => ['label' => 'Every month on the 1(st/nd/rd/th) day', 'selected' => false],
            'ndom,1,1'          => ['label' => 'Every month on the 1(st/nd/rd/th) Monday', 'selected' => false],
            'weekly,1'          => ['label' => 'Every week on Monday', 'selected' => false],
            'yearly,2018-01-01' => ['label' => 'Every year on January  1', 'selected' => false],
        ];

        $response->assertExactJson($expected);
    }
}

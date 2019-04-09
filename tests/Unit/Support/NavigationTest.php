<?php
/**
 * NavigationTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Support;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Navigation;
use Log;
use Tests\TestCase;

/**
 *
 * Class NavigationTest
 */
class NavigationTest extends TestCase
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
     * @covers \FireflyIII\Support\Navigation
     */
    public function testAddPeriod(): void
    {
        $tests = [
            // period, skip, start, expected end
            ['1D', 0, '2018-01-01', '2018-01-02'],
            ['1D', 1, '2018-01-01', '2018-01-03'],
            ['1D', 1, '2018-02-28', '2018-03-02'],
            ['1W', 0, '2015-02-28', '2015-03-07'],
            ['1W', 0, '2016-02-28', '2016-03-06'], // leap year
            ['1W', 1, '2015-02-28', '2015-03-14'],
            ['1W', 1, '2016-02-28', '2016-03-13'], // leap year
            ['1W', 0, '2018-01-01', '2018-01-08'],

            ['1M', 0, '2018-01-01', '2018-02-01'],
            ['1M', 0, '2018-02-01', '2018-03-01'],
            ['1M', 0, '2018-01-28', '2018-02-28'],
            ['1M', 0, '2016-01-29', '2016-02-29'], // leap year makes it work
            ['1M', 0, '2019-01-29', '2019-02-28'], // jump to end of next month.
            ['1M', 0, '2019-01-30', '2019-02-28'], // jump to end of next month.
            ['1M', 0, '2019-01-31', '2019-02-28'], // jump to end of next month.
            ['1M', 0, '2019-02-01', '2019-03-01'],
            ['1M', 1, '2019-02-01', '2019-03-31'], // weird but OK.
            ['1M', 2, '2019-01-01', '2019-04-01'],

            ['quarter', 0, '2019-01-01', '2019-04-01'],
            ['quarter', 1, '2019-01-01', '2019-07-01'],

            ['6M', 0, '2019-01-01', '2019-07-01'],
            ['6M', 1, '2019-01-01', '2020-01-01'],
            ['6M', 0, '2019-08-01', '2020-02-01'],

            ['year', 0, '2019-01-01', '2020-01-01'],
            ['year', 1, '2019-01-01', '2021-01-01'],
            ['custom', 1, '2019-01-01', '2019-03-01'],

        ];

        /** @var array $test */
        foreach ($tests as $test) {

            $freq = $test[0];
            /** @noinspection MultiAssignmentUsageInspection */
            $skip     = $test[1];
            $start    = new Carbon($test[2]);
            $expected = new Carbon($test[3]);
            $nav      = new Navigation;
            try {
                $result = $nav->addPeriod($start, $freq, $skip);
            } catch (FireflyException $e) {
                $this->assertFalse(true, $e->getMessage());
            }

            $this->assertEquals($expected->format('Y-m-d'), $result->format('Y-m-d'));
        }
    }

    /**
     * @covers \FireflyIII\Support\Navigation
     */
    public function testAddPeriodError(): void
    {
        $tests = [
            // period, skip, start, expected end
            ['bla', 0, '2018-01-01', '2018-01-02'],
        ];

        /** @var array $test */
        foreach ($tests as $test) {

            $freq = $test[0];
            /** @noinspection MultiAssignmentUsageInspection */
            $skip  = $test[1];
            $start = new Carbon($test[2]);
            $nav   = new Navigation;
            try {
                $nav->addPeriod($start, $freq, $skip);
            } catch (FireflyException $e) {
                $this->assertEquals('Cannot do addPeriod for $repeat_freq "bla"', $e->getMessage());
            }
        }
    }

    /**
     * @covers \FireflyIII\Support\Navigation
     */
    public function testBlockPeriods(): void
    {
        $tests = [
            [
                'start'    => '2014-01-01',
                'end'      => '2018-12-31',
                'range'    => '1M',
                'expected' =>
                    [
                        [
                            'start'  => '2018-12-01',
                            'end'    => '2018-12-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-11-01',
                            'end'    => '2018-11-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-10-01',
                            'end'    => '2018-10-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-09-01',
                            'end'    => '2018-09-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-08-01',
                            'end'    => '2018-08-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-07-01',
                            'end'    => '2018-07-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-06-01',
                            'end'    => '2018-06-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-05-01',
                            'end'    => '2018-05-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-04-01',
                            'end'    => '2018-04-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-03-01',
                            'end'    => '2018-03-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-02-01',
                            'end'    => '2018-02-28',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2018-01-01',
                            'end'    => '2018-01-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-12-01',
                            'end'    => '2017-12-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-11-01',
                            'end'    => '2017-11-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-10-01',
                            'end'    => '2017-10-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-09-01',
                            'end'    => '2017-09-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-08-01',
                            'end'    => '2017-08-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-07-01',
                            'end'    => '2017-07-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-06-01',
                            'end'    => '2017-06-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-05-01',
                            'end'    => '2017-05-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-04-01',
                            'end'    => '2017-04-30',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-03-01',
                            'end'    => '2017-03-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-02-01',
                            'end'    => '2017-02-28',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2017-01-01',
                            'end'    => '2017-01-31',
                            'period' => '1M',
                        ],
                        [
                            'start'  => '2016-01-01',
                            'end'    => '2016-12-31',
                            'period' => '1Y',
                        ],
                        [
                            'start'  => '2015-01-01',
                            'end'    => '2015-12-31',
                            'period' => '1Y',
                        ],
                        [
                            'start'  => '2014-01-01',
                            'end'    => '2014-12-31',
                            'period' => '1Y',
                        ],
                    ]

                ,
            ],
        ];

        /** @var array $test */
        foreach ($tests as $test) {

            $start = new Carbon($test['start']);
            $end   = new Carbon($test['end']);
            $range = $test['range'];
            $nav   = new Navigation;
            try {
                $result = $nav->blockPeriods($start, $end, $range);
            } catch (FireflyException $e) {
                $this->assertFalse(true, $e->getMessage());
            }
            $parsedResult = [];
            foreach ($result as $entry) {
                $parsedResult[] = [
                    'start'  => $entry['start']->format('Y-m-d'),
                    'end'    => $entry['end']->format('Y-m-d'),
                    'period' => $entry['period'],
                ];
            }
            $this->assertEquals($test['expected'], $parsedResult);

        }
    }

    /**
     * @covers \FireflyIII\Support\Navigation
     */
    public function testEndOfPeriod(): void
    {

        $tests = [
            ['1D', '2018-01-01 00:00:00', '2018-01-01 23:59:59'],
            ['1W', '2018-01-01 00:00:00', '2018-01-07 23:59:59'],
            ['1M', '2018-01-01 00:00:00', '2018-01-31 23:59:59'],
            ['3M', '2018-01-01 00:00:00', '2018-03-31 23:59:59'],
            ['6M', '2018-01-01 00:00:00', '2018-06-30 23:59:59'],
            ['1Y', '2018-01-01 00:00:00', '2018-12-31 23:59:59'],
        ];

        /** @var array $test */
        foreach ($tests as $test) {

            $freq = $test[0];
            /** @noinspection MultiAssignmentUsageInspection */
            $start    = new Carbon($test[1]);
            $expected = new Carbon($test[2]);
            $nav      = new Navigation;
            try {
                $result = $nav->endOfPeriod($start, $freq);
            } catch (FireflyException $e) {
                $this->assertFalse(true, $e->getMessage());
            }

            $this->assertEquals($expected->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
        }
    }
}
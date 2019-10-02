<?php
/**
 * ChartJsGeneratorTest.php
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

namespace Tests\Unit\Generator\Chart\Basic;

use FireflyIII\Generator\Chart\Basic\ChartJsGenerator;
use Log;
use Tests\TestCase;

/**
 *
 * Class ChartJsGeneratorTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChartJsGeneratorTest extends TestCase
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
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testBasic(): void
    {

        $data = [
            [
                'label'   => 'Today',
                'fill'    => '#abcdef',
                'yAxisID' => 'a',
                'entries' => [
                    'one'   => 1,
                    'two'   => 2,
                    'three' => 3,
                    'four'  => 4,
                    'five'  => 5,
                ],
            ],
            [

                'currency_symbol' => 'X',
                'backgroundColor' => '#123456',
                'label'           => 'Tomorrow',
                'entries'         => [
                    'one'   => 6,
                    'two'   => 7,
                    'three' => 8,
                    'four'  => 9,
                    'five'  => 10,
                ],
            ],
        ];


        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);

        $result = $generator->multiSet($data);
        $this->assertEquals('one', $result['labels'][0]);
        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['datasets']);

        $this->assertEquals('a', $result['datasets'][0]['yAxisID']);
        $this->assertEquals('#abcdef', $result['datasets'][0]['fill']);

        $this->assertEquals('X', $result['datasets'][1]['currency_symbol']);
        $this->assertEquals('#123456', $result['datasets'][1]['backgroundColor']);
    }

    /**
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testMultiCurrencyPieChart(): void
    {

        $data = [
            'one'   => ['amount' => -1, 'currency_symbol' => 'a'],
            'two'   => ['amount' => -2, 'currency_symbol' => 'b'],
            'three' => ['amount' => -3, 'currency_symbol' => 'c'],
        ];

        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);
        $result    = $generator->multiCurrencyPieChart($data);

        $this->assertEquals('three', $result['labels'][0]);
        $this->assertEquals(3.0, $result['datasets'][0]['data'][0]);

    }

    /**
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testMultiCurrencyPieChartPositive(): void
    {

        $data = [
            'one'   => ['amount' => 1, 'currency_symbol' => 'a'],
            'two'   => ['amount' => 2, 'currency_symbol' => 'b'],
            'three' => ['amount' => 3, 'currency_symbol' => 'c'],
        ];

        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);
        $result    = $generator->multiCurrencyPieChart($data);

        $this->assertEquals('three', $result['labels'][0]);
        $this->assertEquals(3.0, $result['datasets'][0]['data'][0]);

    }

    /**
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testPieChart(): void
    {

        $data = [
            'one'   => -1,
            'two'   => -2,
            'three' => -3,
        ];

        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);
        $result    = $generator->pieChart($data);

        $this->assertEquals('three', $result['labels'][0]);
        $this->assertEquals(3.0, $result['datasets'][0]['data'][0]);

    }

    /**
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testPieChartReversed(): void
    {

        $data = [
            'one'   => 1,
            'two'   => 2,
            'three' => 3,
        ];

        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);
        $result    = $generator->pieChart($data);

        $this->assertEquals('three', $result['labels'][0]);
        $this->assertEquals(3.0, $result['datasets'][0]['data'][0]);

    }

    /**
     * @covers \FireflyIII\Generator\Chart\Basic\ChartJsGenerator
     */
    public function testSingleSet(): void
    {
        $data = [
            'one'   => '1',
            'two'   => '2',
            'three' => '3',
        ];

        /** @var ChartJsGenerator $generator */
        $generator = app(ChartJsGenerator::class);
        $result    = $generator->singleSet('Some label', $data);

        $this->assertEquals('one', $result['labels'][0]);
        $this->assertEquals(1.0, $result['datasets'][0]['data'][0]);
    }

}

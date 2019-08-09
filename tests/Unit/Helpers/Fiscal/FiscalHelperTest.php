<?php
/**
 * FiscalHelperTest.php
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

namespace Tests\Unit\Helpers\Fiscal;


use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelper;
use FireflyIII\Models\Preference;
use Log;
use Preferences;
use Tests\TestCase;


/**
 *
 * Class FiscalHelperTest
 */
class FiscalHelperTest extends TestCase
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
     * Fiscal year starts on April 1st.
     * Current date is June 6th.
     *
     * Fiscal year ends next year on Mar 31.
     *
     * @covers \FireflyIII\Helpers\Fiscal\FiscalHelper
     */
    public function testEndOfFiscalYear(): void
    {

        $pref       = new Preference;
        $pref->data = true;

        $datePref       = new Preference;
        $datePref->data = '04-01';

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($pref)->once();
        Preferences::shouldReceive('get')->withArgs(['fiscalYearStart', '01-01'])->andReturn($datePref)->once();

        $helper = new FiscalHelper;
        $date   = new Carbon('2018-06-06');
        $result = $helper->endOfFiscalYear($date);
        $this->assertEquals('2019-03-31', $result->format('Y-m-d'));
    }

    /**
     * No fiscal year
     * Current date is June 6th.
     *
     * Fiscal year ends next year on Dec 31.
     *
     * @covers \FireflyIII\Helpers\Fiscal\FiscalHelper
     */
    public function testEndOfFiscalYearNoPref(): void
    {

        $pref       = new Preference;
        $pref->data = false;

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($pref)->once();

        $helper = new FiscalHelper;
        $date   = new Carbon('2018-06-06');
        $result = $helper->endOfFiscalYear($date);
        $this->assertEquals('2018-12-31', $result->format('Y-m-d'));
    }

    /**
     * Fiscal year starts on April 1st.
     * Current date is June 6th.
     *
     * Fiscal year starts in current year.
     *
     * @covers \FireflyIII\Helpers\Fiscal\FiscalHelper
     */
    public function testStartOfFiscalYear(): void
    {

        $pref       = new Preference;
        $pref->data = true;

        $datePref       = new Preference;
        $datePref->data = '04-01';

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($pref)->once();
        Preferences::shouldReceive('get')->withArgs(['fiscalYearStart', '01-01'])->andReturn($datePref)->once();

        $helper = new FiscalHelper;
        $date   = new Carbon('2018-06-06');
        $result = $helper->startOfFiscalYear($date);
        $this->assertEquals('2018-04-01', $result->format('Y-m-d'));
    }

    /**
     * No fiscal year
     * Current date is June 6th.
     *
     * Fiscal year starts Jan 1st.
     *
     * @covers \FireflyIII\Helpers\Fiscal\FiscalHelper
     */
    public function testStartOfFiscalYearNoPref(): void
    {

        $pref       = new Preference;
        $pref->data = false;

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($pref)->once();

        $helper = new FiscalHelper;
        $date   = new Carbon('2018-06-06');
        $result = $helper->startOfFiscalYear($date);
        $this->assertEquals('2018-01-01', $result->format('Y-m-d'));
    }

    /**
     * Fiscal year starts on April 1st.
     * Current date is Feb 6th.
     *
     * Fiscal year starts in previous year.
     *
     * @covers \FireflyIII\Helpers\Fiscal\FiscalHelper
     */
    public function testStartOfFiscalYearPrev(): void
    {

        $pref       = new Preference;
        $pref->data = true;

        $datePref       = new Preference;
        $datePref->data = '04-01';

        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($pref)->once();
        Preferences::shouldReceive('get')->withArgs(['fiscalYearStart', '01-01'])->andReturn($datePref)->once();

        $helper = new FiscalHelper;
        $date   = new Carbon('2018-02-06');
        $result = $helper->startOfFiscalYear($date);
        $this->assertEquals('2017-04-01', $result->format('Y-m-d'));
    }
}

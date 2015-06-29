<?php

use Carbon\Carbon;
use FireflyIII\Support\Navigation;

/**
 * Class NavigationTest
 */
class NavigationTest extends TestCase
{
    /**
     * @var Navigation
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Navigation;
    }

    /**
     * @covers FireflyIII\Support\Navigation::addPeriod
     */
    public function testAddPeriod()
    {
        $date = new Carbon('2015-01-01');

        $result = $this->object->addPeriod($date, 'quarter', 0);
        $this->assertEquals('2015-04-01', $result->format('Y-m-d'));
    }

    /**
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot do addPeriod for $repeat_freq "something"
     * @covers                   FireflyIII\Support\Navigation::addPeriod
     */
    public function testAddPeriodError()
    {
        $date = new Carbon('2015-01-01');

        $this->object->addPeriod($date, 'something', 0);

    }

    /**
     * @covers FireflyIII\Support\Navigation::endOfPeriod
     */
    public function testEndOfPeriod()
    {
        $date = new Carbon('2015-01-01');

        $result = $this->object->endOfPeriod($date, '1D');
        $this->assertEquals('2015-01-02', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::endOfPeriod
     */
    public function testEndOfPeriodModifier()
    {
        $date = new Carbon('2015-01-01');

        $result = $this->object->endOfPeriod($date, 'quarter');
        $this->assertEquals('2015-03-31', $result->format('Y-m-d'));
    }

    /**
     * @covers                   FireflyIII\Support\Navigation::endOfPeriod
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot do endOfPeriod for $repeat_freq "something"
     */
    public function testEndOfPeriodModifierError()
    {
        $date = new Carbon('2015-01-01');

        $this->object->endOfPeriod($date, 'something');
    }

    /**
     * @covers FireflyIII\Support\Navigation::endOfX
     */
    public function testEndOfX()
    {
        $date   = new Carbon('2015-01-01');
        $maxEnd = new Carbon('2016-01-01');

        $result = $this->object->endOfX($date, 'month', $maxEnd);

        $this->assertEquals('2015-01-31', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::endOfX
     */
    public function testEndOfXBeyondMax()
    {
        $date   = new Carbon('2015-01-01');
        $maxEnd = new Carbon('2015-01-15');

        $result = $this->object->endOfX($date, 'monthly', $maxEnd);

        $this->assertEquals('2015-01-15', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::periodShow
     */
    public function testPeriodShow()
    {
        $date   = new Carbon('2015-01-01');
        $result = $this->object->periodShow($date, 'month');
        $this->assertEquals('January 2015', $result);
    }

    /**
     * @covers                   FireflyIII\Support\Navigation::periodShow
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage No date formats for frequency "something"
     */
    public function testPeriodShowError()
    {
        $date = new Carbon('2015-01-01');
        $this->object->periodShow($date, 'something');
    }


    /**
     * @covers FireflyIII\Support\Navigation::startOfPeriod
     */
    public function testStartOfPeriod()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->startOfPeriod($date, 'month');
        $this->assertEquals('2015-01-01', $result->format('Y-m-d'));
    }

    /**
     * @covers                   FireflyIII\Support\Navigation::startOfPeriod
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot do startOfPeriod for $repeat_freq "something"
     */
    public function testStartOfPeriodError()
    {
        $date = new Carbon('2015-08-15');
        $this->object->startOfPeriod($date, 'something');
    }

    /**
     * @covers FireflyIII\Support\Navigation::startOfPeriod
     */
    public function testStartOfPeriodHalfYear()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->startOfPeriod($date, 'half-year');
        $this->assertEquals('2015-01-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::startOfPeriod
     */
    public function testStartOfPeriodHalfYearSecondHalf()
    {
        $date   = new Carbon('2015-08-15');
        $result = $this->object->startOfPeriod($date, 'half-year');
        $this->assertEquals('2015-07-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::subtractPeriod
     */
    public function testSubtractPeriod()
    {
        $date   = new Carbon('2015-01-01');
        $result = $this->object->subtractPeriod($date, 'month');
        $this->assertEquals('2014-12-01', $result->format('Y-m-d'));
    }

    /**
     * @covers                   FireflyIII\Support\Navigation::subtractPeriod
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot do subtractPeriod for $repeat_freq "something"
     */
    public function testSubtractPeriodError()
    {
        $date = new Carbon('2015-01-01');
        $this->object->subtractPeriod($date, 'something');
    }

    /**
     * @covers FireflyIII\Support\Navigation::subtractPeriod
     */
    public function testSubtractPeriodQuarter()
    {
        $date   = new Carbon('2015-01-01');
        $result = $this->object->subtractPeriod($date, 'quarter');
        $this->assertEquals('2014-10-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateEndDate
     */
    public function testUpdateEndDate()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->updateEndDate('1M', $date);
        $this->assertEquals('2015-01-31', $result->format('Y-m-d'));
    }

    /**
     * @covers                   FireflyIII\Support\Navigation::updateEndDate
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage updateEndDate cannot handle $range "something"
     */
    public function testUpdateEndDateError()
    {
        $date = new Carbon('2015-01-15');
        $this->object->updateEndDate('something', $date);
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateEndDate
     */
    public function testUpdateEndDateHalf()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->updateEndDate('6M', $date);
        $this->assertEquals('2015-07-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateEndDate
     */
    public function testUpdateEndDateSecondHalf()
    {
        $date   = new Carbon('2015-08-15');
        $result = $this->object->updateEndDate('6M', $date);
        $this->assertEquals('2015-12-31', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateStartDate
     */
    public function testUpdateStartDate()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->updateStartDate('1M', $date);
        $this->assertEquals('2015-01-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateStartDate
     */
    public function testUpdateStartDateHalf()
    {
        $date   = new Carbon('2015-01-15');
        $result = $this->object->updateStartDate('6M', $date);
        $this->assertEquals('2015-01-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateStartDate
     */
    public function testUpdateStartDateSecondHalf()
    {
        $date   = new Carbon('2015-09-15');
        $result = $this->object->updateStartDate('6M', $date);
        $this->assertEquals('2015-07-01', $result->format('Y-m-d'));
    }

    /**
     * @covers FireflyIII\Support\Navigation::updateStartDate
     * @expectedException FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage updateStartDate cannot handle $range "something"
     */
    public function testUpdateStartDateError()
    {
        $date   = new Carbon('2015-09-15');
        $this->object->updateStartDate('something', $date);
    }


}
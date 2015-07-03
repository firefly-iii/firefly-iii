<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Report\GoogleReportChartGenerator;
use Illuminate\Support\Collection;

/**
 * Class GoogleReportChartGeneratorTest
 */
class GoogleReportChartGeneratorTest extends TestCase
{
    /** @var GoogleReportChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GoogleReportChartGenerator;

    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * FireflyIII\Generator\Chart\Report\GoogleReportChartGenerator::yearInOut
     */
    public function testYearInOut()
    {
        // make set:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([new Carbon, 200, 100]);
        }

        $data = $this->object->yearInOut($collection);

        $this->assertCount(5, $data['rows']);

        $this->assertEquals(200, $data['rows'][0]['c'][1]['v']);
        $this->assertEquals(100, $data['rows'][0]['c'][2]['v']);
    }

    /**
     * FireflyIII\Generator\Chart\Report\GoogleReportChartGenerator::yearInOutSummarized
     */
    public function testYearInOutSummarized()
    {
        // make set:
        $income  = 2400;
        $expense = 1200;

        $data = $this->object->yearInOutSummarized($income, $expense, 12);

        $this->assertEquals(200, $data['rows'][1]['c'][1]['v']);
        $this->assertEquals(100, $data['rows'][1]['c'][2]['v']);
    }
}
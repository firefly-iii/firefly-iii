<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Report\ChartJsReportChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * Class ChartJsReportChartGeneratorTest
 */
class ChartJsReportChartGeneratorTest extends TestCase
{

    /** @var ChartJsReportChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new ChartJsReportChartGenerator;

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
     * FireflyIII\Generator\Chart\Report\ChartJsReportChartGenerator::yearInOut
     */
    public function testYearInOut()
    {
        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        // make set:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([new Carbon, 200, 100]);
        }

        $data = $this->object->yearInOut($collection);

        $this->assertEquals(200, $data['datasets'][0]['data'][0]);
        $this->assertEquals(100, $data['datasets'][1]['data'][0]);
        $this->assertCount(5, $data['labels']);

    }

    /**
     * FireflyIII\Generator\Chart\Report\ChartJsReportChartGenerator::yearInOutSummarized
     */
    public function testYearInOutSummarized()
    {
        // make set:
        $income  = 2400;
        $expense = 1200;

        $data = $this->object->yearInOutSummarized($income, $expense, 12);

        $this->assertEquals(200, $data['datasets'][0]['data'][1]);
        $this->assertEquals(100, $data['datasets'][1]['data'][1]);

    }
}
<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\PiggyBank\GooglePiggyBankChartGenerator;
use Illuminate\Support\Collection;

/**
 * Class GooglePiggyBankChartGeneratorTest
 */
class GooglePiggyBankChartGeneratorTest extends TestCase
{

    /** @var GooglePiggyBankChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GooglePiggyBankChartGenerator();

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
     * @covers FireflyIII\Generator\Chart\PiggyBank\GooglePiggyBankChartGenerator::history
     */
    public function testHistory()
    {
        // create a set
        $set = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $obj       = new stdClass;
            $obj->date = new Carbon;
            $obj->sum  = 100;
            $set->push($obj);
        }

        $data = $this->object->history($set);
        $this->assertCount(5, $data['rows']);
        $this->assertCount(2, $data['cols']);

        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);
        $this->assertEquals(500, $data['rows'][4]['c'][1]['v']);
    }
}
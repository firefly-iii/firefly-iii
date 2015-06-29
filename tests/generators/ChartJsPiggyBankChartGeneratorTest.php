<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\PiggyBank\ChartJsPiggyBankChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartJsPiggyBankChartGeneratorTest
 */
class ChartJsPiggyBankChartGeneratorTest extends TestCase
{
    /** @var ChartJsPiggyBankChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new ChartJsPiggyBankChartGenerator;

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
     * @covers FireflyIII\Generator\Chart\PiggyBank\ChartJsPiggyBankChartGenerator::history
     */
    public function testHistory()
    {
        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        // create a set
        $set = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $obj       = new stdClass;
            $obj->date = new Carbon;
            $obj->sum  = 100;
            $set->push($obj);
        }

        $data = $this->object->history($set);
        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        $this->assertEquals(100, $data['datasets'][0]['data'][0]);
        $this->assertEquals(500, $data['datasets'][0]['data'][4]);


    }
}
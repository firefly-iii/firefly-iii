<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class GoogleBillChartGeneratorTest
 */
class ChartJsBillChartGeneratorTest extends TestCase
{

    /** @var \FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new ChartJsBillChartGenerator();


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
     * @covers FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // to test frontpage, we generate the exact fake entries
        // needed:
        $paid = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $obj              = new stdClass();
            $obj->description = 'Something';
            $obj->amount      = 100;
            $paid->push($obj);
        }

        $unpaid = new Collection;
        $sum    = 0;
        for ($i = 0; $i < 5; $i++) {
            $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
            $date = new Carbon;
            $sum += (($bill->amount_max + $bill->amount_min) / 2);
            $unpaid->push([$bill, $date]);
        }


        $data = $this->object->frontpage($paid, $unpaid);

        $this->assertCount(2, $data);
        $this->assertEquals($sum, $data[0]['value']);
        $this->assertEquals(500, $data[1]['value']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator::single
     */
    public function testSingle()
    {

        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        $bill    = FactoryMuffin::create('FireflyIII\Models\Bill');
        $entries = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $obj         = new stdClass;
            $obj->amount = 100;
            $obj->date   = new Carbon;
            $entries->push($obj);
        }
        $data = $this->object->single($bill, $entries);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][1]['data']);
        $this->assertEquals(100, $data['datasets'][1]['data'][0]); // see if first is equal.


    }
}
<?php

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Bill\GoogleBillChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * Class GoogleBillChartGeneratorTest
 */
class GoogleBillChartGeneratorTest extends TestCase
{

    /** @var GoogleBillChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GoogleBillChartGenerator;

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
     * @covers FireflyIII\Generator\Chart\Bill\GoogleBillChartGenerator::frontpage
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

        $this->assertCount(2, $data['cols']);
        $this->assertCount(2, $data['rows']); // two rows, two columns.
    }

    /**
     * @covers FireflyIII\Generator\Chart\Bill\GoogleBillChartGenerator::single
     */
    public function testSingle()
    {
        $bill    = FactoryMuffin::create('FireflyIII\Models\Bill');
        $entries = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $obj         = new stdClass;
            $obj->amount = 100;
            $obj->date   = new Carbon;
            $entries->push($obj);
        }
        $data = $this->object->single($bill, $entries);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(4, $data['cols']);
        $this->assertEquals(100, $data['rows'][0]['c'][3]['v']);
    }
}
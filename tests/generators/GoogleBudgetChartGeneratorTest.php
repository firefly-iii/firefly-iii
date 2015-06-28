<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Budget\GoogleBudgetChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class GoogleBudgetChartGeneratorTest
 */
class GoogleBudgetChartGeneratorTest extends TestCase
{




    /** @var GoogleBudgetChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GoogleBudgetChartGenerator();

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
     * @covers FireflyIII\Generator\Chart\Budget\GoogleBudgetChartGenerator::budget
     */
    public function testBudget()
    {
        // make a collection with some amounts in them.
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([new Carbon, 100]);
        }

        $data = $this->object->budget($collection);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(2, $data['cols']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Budget\GoogleBudgetChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // make a collection with some amounts in them.
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push(['Some label', 100, 200, 300]);
        }

        $data = $this->object->frontpage($collection);

        $this->assertCount(5, $data['rows']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);
        $this->assertEquals(200, $data['rows'][0]['c'][2]['v']);
        $this->assertEquals(300, $data['rows'][0]['c'][3]['v']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Budget\GoogleBudgetChartGenerator::year
     */
    public function testYear()
    {
        $budgets = new Collection;
        $entries = new Collection;

        // make some budgets:
        for ($i = 0; $i < 5; $i++) {
            $budgets->push(FactoryMuffin::create('FireflyIII\Models\Budget'));
            $entries->push([new Carbon, 100, 100, 100]);
        }

        $data = $this->object->year($budgets, $entries);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(6, $data['cols']);
    }
}
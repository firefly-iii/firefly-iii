<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartJsBudgetChartGeneratorTest
 */
class ChartJsBudgetChartGeneratorTest extends TestCase
{


    /** @var ChartJsBudgetChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new ChartJsBudgetChartGenerator();

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
     * @covers FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator::budget
     */
    public function testBudget()
    {
        // make a collection with some amounts in them.
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([null, 100]);
        }

        $data = $this->object->budget($collection);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        $this->assertEquals(100, $data['datasets'][0]['data'][0]);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // make a collection with some amounts in them.
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push(['Some label', 100, 200, 300]);
        }

        $data = $this->object->frontpage($collection);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        $this->assertEquals(100, $data['datasets'][0]['data'][0]);
        $this->assertEquals(200, $data['datasets'][1]['data'][0]);
        $this->assertEquals(300, $data['datasets'][2]['data'][0]);

    }

    /**
     * @covers FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator::year
     */
    public function testYear()
    {
        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        $budgets = new Collection;
        $entries = new Collection;

        // make some budgets:
        for ($i = 0; $i < 5; $i++) {
            $budgets->push(FactoryMuffin::create('FireflyIII\Models\Budget'));
            $entries->push([new Carbon, 100, 100, 100]);
        }

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        $data = $this->object->year($budgets, $entries);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets']);
        $this->assertCount(3, $data['datasets'][0]['data']);

    }
}
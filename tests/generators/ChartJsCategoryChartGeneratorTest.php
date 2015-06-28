<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * Class ChartJsCategoryChartGeneratorTest
 */
class ChartJsCategoryChartGeneratorTest extends TestCase
{


    /** @var ChartJsCategoryChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new ChartJsCategoryChartGenerator;

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
     * @covers FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator::all
     */
    public function testAll()
    {
        // make a collection of stuff:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([null, 100]);
        }

        $data = $this->object->all($collection);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        $this->assertEquals(100, $data['datasets'][0]['data'][0]);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // make a collection of stuff:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push(['name' => 'Something', 'sum' => 100]);
        }

        $data = $this->object->frontpage($collection);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        $this->assertEquals('Something', $data['labels'][0]);
        $this->assertEquals(100, $data['datasets'][0]['data'][0]);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator::year
     */
    public function testYear()
    {
        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        // make a collection of stuff:
        $collection = new Collection;
        $categories = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $categories->push(FactoryMuffin::create('FireflyIII\Models\Category'));
            $collection->push([new Carbon, 100, 100, 100]);
        }

        $data = $this->object->year($categories, $collection);

        $this->assertCount(5, $data['labels']);
        $this->assertEquals($categories->first()->name, $data['labels'][0]);
        $this->assertCount(3, $data['datasets'][0]['data']);


    }
}
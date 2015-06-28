<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class GoogleCategoryChartGeneratorTest
 */
class GoogleCategoryChartGeneratorTest extends TestCase
{


    /** @var GoogleCategoryChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GoogleCategoryChartGenerator();

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
     * @covers FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator::all
     */
    public function testAll()
    {
        // make a collection of stuff:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([new Carbon, 100]);
        }

        $data = $this->object->all($collection);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(2, $data['cols']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // make a collection of stuff:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push(['name' => 'Something', 'sum' => 100]);
        }

        $data = $this->object->frontpage($collection);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(2, $data['cols']);
        $this->assertEquals('Something', $data['rows'][0]['c'][0]['v']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);

    }

    /**
     * @covers FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator::month
     */
    public function testMonth()
    {
        // make a collection of stuff:
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push([new Carbon, 100]);
        }

        $data = $this->object->month($collection);

        $this->assertCount(5, $data['rows']);
        $this->assertCount(2, $data['cols']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator::year
     */
    public function testYear()
    {
        // make a collection of stuff:
        $collection = new Collection;
        $categories = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $categories->push(FactoryMuffin::create('FireflyIII\Models\Category'));
            $collection->push([new Carbon, 100, 100, 100]);
        }

        $data = $this->object->year($categories, $collection);

        $this->assertCount(5, $data['rows']);
        $this->assertEquals($categories->first()->name, $data['cols'][1]['label']);
        $this->assertEquals(100, $data['rows'][0]['c'][1]['v']);

    }
}
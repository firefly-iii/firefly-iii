<?php

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartJsAccountChartGeneratorTest
 */
class ChartJsAccountChartGeneratorTest extends TestCase
{

    /**
     * @var ChartJsAccountChartGenerator
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->object = new ChartJsAccountChartGenerator;

        parent::setUp();


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
     * @covers FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // be somebody
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // create some accounts:
        $accounts = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $accounts->push(FactoryMuffin::create('FireflyIII\Models\Account'));
        }

        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // data for call:
        $start = Carbon::createFromDate(2015, 1, 1);
        $end   = Carbon::createFromDate(2015, 1, 15);

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        // mock Steam::balance
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        // call
        $result = $this->object->frontpage($accounts, $start, $end);

        $this->assertEquals($accounts->count(), $result['count']);
        $this->assertCount(15, $result['labels']);
        $this->assertCount($accounts->count(), $result['datasets']);
    }

    /**
     * @covers FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator::single
     */
    public function testSingle()
    {
        // be somebody
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $preference->data = 'en';
        $preference->save();

        // mock language preference:
        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);

        // mock Steam::balance
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        // data for call:
        $start   = Carbon::createFromDate(2015, 1, 1);
        $end     = Carbon::createFromDate(2015, 1, 15);
        $account = FactoryMuffin::create('FireflyIII\Models\Account');

        // call
        $result = $this->object->single($account, $start, $end);


        // test
        $this->assertCount(15, $result['labels']);
        $this->assertEquals($account->name, $result['datasets'][0]['label']);
        $this->assertCount(15, $result['datasets'][0]['data']);


    }
}
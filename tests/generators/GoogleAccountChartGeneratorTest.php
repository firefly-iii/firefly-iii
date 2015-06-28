<?php
use Carbon\Carbon;
use FireflyIII\Generator\Chart\Account\GoogleAccountChartGenerator;
use FireflyIII\Models\Account;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class GoogleAccountChartGeneratorTest
 */
class GoogleAccountChartGeneratorTest extends TestCase
{

    /** @var GoogleAccountChartGenerator */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->object = new GoogleAccountChartGenerator;

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
     * @covers FireflyIII\Generator\Chart\Account\GoogleAccountChartGenerator::all
     */
    public function testAll()
    {
        // be somebody
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // create some accounts:
        $accounts = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $accounts->push(FactoryMuffin::create('FireflyIII\Models\Account'));
        }

        // data for call:
        $start = Carbon::createFromDate(2015, 1, 1);
        $end   = Carbon::createFromDate(2015, 1, 15);

        // mock Steam::balance
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        $data = $this->object->all($accounts, $start, $end);
        $this->assertCount(11, $data['cols']); // accounts * 2 + date.
        // fifteen days,
        $this->assertCount(16, $data['rows']); // 15 + 1?
    }

    /**
     * @covers FireflyIII\Generator\Chart\Account\GoogleAccountChartGenerator::frontpage
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

        // data for call:
        $start = Carbon::createFromDate(2015, 1, 1);
        $end   = Carbon::createFromDate(2015, 1, 15);

        // mock Steam::balance
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        $data = $this->object->frontpage($accounts, $start, $end);
        $this->assertCount(11, $data['cols']); // accounts * 2 + date.
        // fifteen days,
        $this->assertCount(16, $data['rows']); // 15 + 1?
    }

    /**
     * @covers FireflyIII\Generator\Chart\Account\GoogleAccountChartGenerator::single
     */
    public function testSingle()
    {
        // be somebody
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        /** @var Account $account */
        $account = FactoryMuffin::create('FireflyIII\Models\Account');

        // data for call:
        $start = Carbon::createFromDate(2015, 1, 1);
        $end   = Carbon::createFromDate(2015, 1, 15);

        // mock Steam::balance
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        $data = $this->object->single($account, $start, $end);
        $this->assertCount(3, $data['cols']); // account, date, certainty
        // fifteen days,
        $this->assertCount(15, $data['rows']); // 15 days
    }

}
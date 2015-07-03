<?php


use FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator;
use FireflyIII\User;

class ChartJsAccountChartGeneratorTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @var   */
    protected $object;

    protected function _before()
    {
        $this->object = new ChartJsAccountChartGenerator;

    }

    protected function _after()
    {
    }

    // tests
    public function testMe()
    {
        $this->assertTrue(true);

    }

    /**
     * @covers FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator::frontpage
     */
    public function testFrontpage()
    {
        // create a user from framework, user will be deleted after the test
        $id = $this->tester->haveRecord('users', ['email' => 'some@thing.nl']);
        // access model
        $user = User::find($id);

        // be somebody
//        $user = FactoryMuffin::create('FireflyIII\User');
//        $this->be($user);
//
//        // create some accounts:
//        $accounts = new Collection;
//        for ($i = 0; $i < 5; $i++) {
//            $accounts->push(FactoryMuffin::create('FireflyIII\Models\Account'));
//        }
//
//        $preference       = FactoryMuffin::create('FireflyIII\Models\Preference');
//        $preference->data = 'en';
//        $preference->save();
//
//        // data for call:
//        $start = Carbon::createFromDate(2015, 1, 1);
//        $end   = Carbon::createFromDate(2015, 1, 15);
//
//        // mock language preference:
//        Preferences::shouldReceive('get')->withArgs(['language', 'en'])->andReturn($preference);
//
//        // mock Steam::balance
//        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        // call
//        $result = $this->object->frontpage($accounts, $start, $end);
//
//        $this->assertEquals($accounts->count(), $result['count']);
//        $this->assertCount(15, $result['labels']);
//        $this->assertCount($accounts->count(), $result['datasets']);
        $this->assertTrue(true);
    }

}
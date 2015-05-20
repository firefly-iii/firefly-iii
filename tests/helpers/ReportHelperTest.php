<?php

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ReportHelperTest
 */
class ReportHelperTest extends TestCase
{
    /**
     * @var ReportHelper
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        FactoryMuffin::create('FireflyIII\User');
        $query = new \FireflyIII\Helpers\Report\ReportQuery();
        $this->object = new ReportHelper($query);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testListOfMonths()
    {
        // start of year up until now
        $date   = new Carbon('2015-01-01');
        $now    = new Carbon;
        $diff   = $now->diffInMonths($date) + 1; // the month itself.
        $result = $this->object->listOfMonths($date);

        $this->assertCount($diff, $result[2015]);

    }

}

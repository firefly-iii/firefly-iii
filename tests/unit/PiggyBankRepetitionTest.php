<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class PiggyBankRepetitionTest
 */
class PiggyBankRepetitionTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testPiggyBankRepetitionScope()
    {
        $repetition = f::create('PiggyBankRepetition');
        $start      = clone $repetition->startdate;
        $target     = clone $repetition->targetdate;

        $this->assertCount(1, PiggyBankRepetition::starts($start)->get());
        $this->assertCount(1, PiggyBankRepetition::targets($target)->get());
    }
}
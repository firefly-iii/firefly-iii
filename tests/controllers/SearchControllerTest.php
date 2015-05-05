<?php

use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class SearchControllerTest
 */
class SearchControllerTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
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
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testSearch()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $words = ['Something'];
        // mock!
        $repository = $this->mock('FireflyIII\Support\Search\SearchInterface');
        $repository->shouldReceive('searchTransactions')->with($words)->once()->andReturn([]);
        $repository->shouldReceive('searchAccounts')->with($words)->once()->andReturn([]);
        $repository->shouldReceive('searchCategories')->with($words)->once()->andReturn([]);
        $repository->shouldReceive('searchBudgets')->with($words)->once()->andReturn([]);
        $repository->shouldReceive('searchTags')->with($words)->once()->andReturn([]);

        $this->call('GET', '/search?q=Something');
        $this->assertResponseOk();
    }
}
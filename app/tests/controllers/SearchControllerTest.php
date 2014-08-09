<?php
use Mockery as m;

/**
 * Class SearchControllerTest
 */
class SearchControllerTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

    }

    public function tearDown()
    {
        m::close();
    }

    public function testIndex()
    {
        $this->action('GET', 'SearchController@index');
        $this->assertResponseOk();
    }

} 
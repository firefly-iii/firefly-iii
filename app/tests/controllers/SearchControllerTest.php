<?php
use Mockery as m;

/**
 * Class SearchControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
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
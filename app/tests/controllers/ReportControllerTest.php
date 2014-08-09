<?php
use Mockery as m;

/**
 * Class ReportControllerTest
 */
class ReportControllerTest extends TestCase
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
        $this->action('GET', 'ReportController@index');
        $this->assertResponseOk();
    }

} 
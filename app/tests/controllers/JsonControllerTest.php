<?php

class JsonControllerTest extends TestCase {
    public function setUp()
    {
        parent::setUp();
    }

    public function testBeneficiaries() {

        $obj = new stdClass;
        $obj->name = 'Bla bla';

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getBeneficiaries')->andReturn([$obj]);

        $this->call('GET', '/json/beneficiaries');

        // test
        $this->assertResponseOk();

    }

    public function testCategories() {
        $obj = new stdClass;
        $obj->name = 'Bla bla';

        // mock category repository:
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('get')->andReturn([$obj]);

        $this->call('GET', '/json/categories');

        // test
        $this->assertResponseOk();
    }

} 
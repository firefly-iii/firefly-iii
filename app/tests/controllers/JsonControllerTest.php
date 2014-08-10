<?php
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class JsonControllerTest
 */
class JsonControllerTest extends TestCase
{
    protected $_accounts;
    protected $_categories;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testBeneficiaries()
    {
        $beneficiary = f::create('Account');

        $this->_accounts->shouldReceive('getBeneficiaries')->once()->andReturn([$beneficiary]);
        $this->action('GET', 'JsonController@beneficiaries');
        $this->assertResponseOk();
    }

    public function testCategories()
    {
        $category = f::create('Category');
        $this->_categories->shouldReceive('get')->once()->andReturn([$category]);
        $this->action('GET', 'JsonController@categories');
        $this->assertResponseOk();
    }
} 
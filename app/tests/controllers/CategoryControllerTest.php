<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery as m;
use League\FactoryMuffin\Facade as f;

/**
 * Class CategoryControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CategoryControllerTest extends TestCase
{
    protected $_repository;
    protected $_user;
    protected $_category;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_repository = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $this->_category = $this->mock('Firefly\Helper\Controllers\CategoryInterface');
        $this->_user = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        $this->action('GET', 'CategoryController@create');
        $this->assertResponseOk();

    }

    public function testDelete()
    {

        $category = f::create('Category');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'CategoryController@delete', $category->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $category = f::create('Category');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'CategoryController@destroy', $category->id);
        $this->assertRedirectedToRoute('categories.index');
        $this->assertSessionHas('success');
    }

    public function testDestroyFails()
    {
        $category = f::create('Category');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_repository->shouldReceive('destroy')->once()->andReturn(false);

        $this->action('POST', 'CategoryController@destroy', $category->id);
        $this->assertRedirectedToRoute('categories.index');
        $this->assertSessionHas('error');
    }

    public function testEdit()
    {
        $category = f::create('Category');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'CategoryController@edit', $category->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        $category = f::create('Category');
        $collection = new Collection();
        $collection->add($category);

        $this->_repository->shouldReceive('get')->with()->once()->andReturn($collection);
        $this->action('GET', 'CategoryController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $category = f::create('Category');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($category->email);
        $this->session(['start' => new Carbon, 'end' => new Carbon]);


        $this->_category->shouldReceive('journalsInRange')->once()->andReturn([]);
        $this->action('GET', 'CategoryController@show', $category->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $category = f::create('Category');
        $this->_repository->shouldReceive('store')->andReturn($category);
        $this->action('POST', 'CategoryController@store');
        $this->assertRedirectedToRoute('categories.index');
    }

    public function testStoreFails()
    {
        $category = f::create('Category');
        unset($category->name);
        $this->_repository->shouldReceive('store')->andReturn($category);
        $this->action('POST', 'CategoryController@store');
        $this->assertRedirectedToRoute('categories.create');
    }

    public function testStoreRecreate()
    {
        $category = f::create('Category');
        $this->_repository->shouldReceive('store')->andReturn($category);
        $this->action('POST', 'CategoryController@store', ['create' => '1']);
        $this->assertRedirectedToRoute('categories.create');
    }

    public function testUpdate()
    {
        $category = f::create('Category');
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_repository->shouldReceive('update')->andReturn($category);

        $this->action('POST', 'CategoryController@update', $category->id);
        $this->assertRedirectedToRoute('categories.index');

    }

    public function testUpdateFails()
    {
        $category = f::create('Category');
        unset($category->name);
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_repository->shouldReceive('update')->andReturn($category);

        $this->action('POST', 'CategoryController@update', [$category->id]);
        $this->assertResponseStatus(302);

    }
}
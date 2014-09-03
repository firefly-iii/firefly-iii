<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use League\FactoryMuffin\Facade as f;
use Mockery as m;

/**
 * Class CategoryTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CategoryTest extends TestCase
{
    protected $_repository;
    protected $_user;
    protected $_category;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_repository = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $this->_category   = $this->mock('Firefly\Helper\Controllers\CategoryInterface');
        $this->_user       = m::mock('User', 'Eloquent');

    }

    /**
     *
     */
    public function tearDown()
    {
        Mockery::close();
    }


    /**
     * @covers \CategoryController::create
     */
    public function testCreate()
    {
        // test the view:
        View::shouldReceive('make')->with('categories.create')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Create a new category')->once();

        $this->action('GET', 'CategoryController@create');
        $this->assertResponseOk();

    }

    /**
     * @covers \CategoryController::delete
     */
    public function testDelete()
    {

        $category = f::create('Category');

        // test the view:
        View::shouldReceive('make')->with('categories.delete')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('category', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Delete category "' . $category->name . '"')->once();

        // for successful binding with the category to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'CategoryController@delete', $category->id);
        $this->assertResponseOk();
    }

    /**
     * @covers \CategoryController::destroy
     */
    public function testDestroy()
    {
        $category = f::create('Category');

        // for successful binding with the category to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // fire the repository:
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        // fire and test:
        $this->action('POST', 'CategoryController@destroy', $category->id);
        $this->assertRedirectedToRoute('categories.index');
        $this->assertSessionHas('success');
    }

    /**
     * @covers \CategoryController::edit
     */
    public function testEdit()
    {
        $category = f::create('Category');

        // for successful binding with the category to edit:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email'); //

        // test the view:
        View::shouldReceive('make')->with('categories.edit')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('category', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Edit category "' . $category->name . '"')->once();


        $this->action('GET', 'CategoryController@edit', $category->id);
        $this->assertResponseOk();
    }

    /**
     * @covers \CategoryController::index
     */
    public function testIndex()
    {
        $category   = f::create('Category');
        $collection = new Collection();
        $collection->add($category);

        $this->_repository->shouldReceive('get')->with()->once()->andReturn($collection);

        View::shouldReceive('make')->with('categories.index')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('categories', $collection)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'All your categories')->once();



        $this->action('GET', 'CategoryController@index');
        $this->assertResponseOk();
    }

    /**
     * @covers \CategoryController::show
     */
    public function testShow()
    {
        $category = f::create('Category');

        // for successful binding with the category to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email'); //

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
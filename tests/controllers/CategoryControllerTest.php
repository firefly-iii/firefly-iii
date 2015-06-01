<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * Class CategoryControllerTest
 */
class CategoryControllerTest extends TestCase
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

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::create
     */
    public function testCreate()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/create');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Create a new category');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::delete
     */
    public function testDelete()
    {

        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/delete/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete category "' . e($category->name) . '"');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::destroy
     */
    public function testDestroy()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('destroy');

        $this->call('POST', '/categories/destroy/' . $category->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'The  category "' . e($category->name) . '" was deleted.');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::edit
     */
    public function testEdit()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/edit/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Edit category "' . e($category->name) . '"');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::index
     */
    public function testIndex()
    {
        $collection = new Collection;
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);
        $collection->push($category);

        Amount::shouldReceive('getCurrencyCode')->andReturn('xx');

        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('getCategories')->andReturn($collection);
        $repository->shouldReceive('getLatestActivity')->andReturn(new Carbon);

        $this->call('GET', '/categories');
        $this->assertResponseOk();
        $this->assertViewHas('categories');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::noCategory
     */
    public function testNoCategory()
    {
        $collection = new Collection;
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $this->be($journal->user);
        $collection->push($journal);

        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('xx');


        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('getWithoutCategory')->andReturn($repository);

        $this->call('GET', '/categories/list/noCategory');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle');


    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::show
     */
    public function testShow()
    {
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $collection = new Collection;
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $this->be($category->user);
        $collection->push($journal);

        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $repository->shouldReceive('getJournals')->andReturn($collection);
        $repository->shouldReceive('countJournals')->andReturn(1);

        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('xx');
        Amount::shouldReceive('formatJournal')->andReturn('xx');

        $this->call('GET', '/categories/show/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('hideCategory', true);

    }


    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::store
     */
    public function testStore()
    {
        // create
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        // mock
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CategoryFormRequest');

        // expect
        $repository->shouldReceive('store')->andReturn($category);
        $request->shouldReceive('input')->andReturn('');

        $this->call('POST', '/categories/store', ['_token' => 'replaceMe', 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'New category "' . $category->name . '" stored!');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::store
     */
    public function testStoreAndRedirect()
    {
        // create
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        // mock:
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CategoryFormRequest');

        // fake:
        $repository->shouldReceive('store')->andReturn($category);
        $request->shouldReceive('input')->andReturn('');


        $this->call('POST', '/categories/store', ['_token' => 'replaceMe', 'create_another' => 1, 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'New category "' . $category->name . '" stored!');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::update
     */
    public function testUpdate()
    {
        // create
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        // mock
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CategoryFormRequest');

        // expect
        $repository->shouldReceive('update')->andReturn($category);
        $request->shouldReceive('input')->andReturn('');

        $this->call('POST', '/categories/update/' . $category->id, ['_token' => 'replaceMe', 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Category "' . $category->name . '" updated.');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CategoryController::update
     */
    public function testUpdateAndRedirect()
    {
        // create
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        // mock
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CategoryFormRequest');

        // expect
        $request->shouldReceive('input')->andReturn('');
        $repository->shouldReceive('update')->andReturn($category);


        $this->call('POST', '/categories/update/' . $category->id, ['_token' => 'replaceMe', 'return_to_edit' => 1, 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Category "' . $category->name . '" updated.');
    }
}

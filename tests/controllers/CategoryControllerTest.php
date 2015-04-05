<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
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

    public function testCreate()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/create');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Create a new category');
    }

    public function testDelete()
    {

        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/delete/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete category' . e($category->name) . '"');
    }

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

    public function testEdit()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        $this->call('GET', '/categories/edit/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Edit category "' . e($category->name) . '"');
    }

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

        $this->call('GET', '/categories/show/' . $category->id);
        $this->assertResponseOk();
        $this->assertViewHas('hideCategory', true);

    }

    public function testStore()
    {
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $repository->shouldReceive('store')->andReturn($category);
        $this->be($category->user);

        $this->call('POST', '/categories/store', ['_token' => 'replaceMe', 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'New category "' . $category->name . '" stored!');
    }

    //
    public function testStoreAndRedirect()
    {
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $repository->shouldReceive('store')->andReturn($category);
        $this->be($category->user);

        $this->call('POST', '/categories/store', ['_token' => 'replaceMe', 'create_another' => 1, 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'New category "' . $category->name . '" stored!');
    }

    public function testUpdate()
    {
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $repository->shouldReceive('update')->andReturn($category);
        $this->be($category->user);

        $this->call('POST', '/categories/update/' . $category->id, ['_token' => 'replaceMe', 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Category "' . $category->name . '" updated.');
    }

    public function testUpdateAndRedirect()
    {
        $category   = FactoryMuffin::create('FireflyIII\Models\Category');
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $repository->shouldReceive('update')->andReturn($category);
        $this->be($category->user);

        $this->call('POST', '/categories/update/' . $category->id, ['_token' => 'replaceMe', 'return_to_edit' => 1, 'name' => 'Bla bla #' . rand(1, 1000)]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Category "' . $category->name . '" updated.');
    }
}
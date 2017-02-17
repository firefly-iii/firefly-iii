<?php
/**
 * TagControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Tests\TestCase;

class TagControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('tags.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('tags.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(TagRepositoryInterface::class);
        $repository->shouldReceive('destroy');

        $this->be($this->user());
        $response = $this->post(route('tags.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('tags.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::index
     * @covers \FireflyIII\Http\Controllers\TagController::__construct
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('tags.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->get(route('tags.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::store
     */
    public function testStore()
    {
        $this->session(['tags.create.url' => 'http://localhost']);
        $data = [
            'tag'     => 'Hello new tag' . rand(999, 10000),
            'tagMode' => 'nothing',
        ];
        $this->be($this->user());
        $response = $this->post(route('tags.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TagController::update
     */
    public function testUpdate()
    {
        $this->session(['tags.edit.url' => 'http://localhost']);
        $data       = [
            'tag'     => 'Hello updated tag' . rand(999, 10000),
            'tagMode' => 'nothing',
        ];
        $repository = $this->mock(TagRepositoryInterface::class);
        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(new Tag);

        $this->be($this->user());
        $response = $this->post(route('tags.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}

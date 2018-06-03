<?php
/**
 * LinkControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;

use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class LinkControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LinkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::__construct
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::create
     */
    public function testCreate(): void
    {

        $this->be($this->user());
        $response = $this->get(route('admin.links.create'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::delete
     */
    public function testDeleteEditable(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hello', 'outward' => 'bye', 'name' => 'Test type']);

        $linkType = LinkType::where('editable', 1)->first();
        $repository->shouldReceive('get')->once()->andReturn(new Collection([$linkType]));
        $repository->shouldReceive('countJournals')->andReturn(2);
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::delete
     */
    public function testDeleteNonEditable(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $linkType   = LinkType::where('editable', 0)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::destroy
     */
    public function testDestroy(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hellox', 'outward' => 'byex', 'name' => 'Test typeX']);

        $linkType = LinkType::where('editable', 1)->first();
        $repository->shouldReceive('find')->andReturn($linkType);
        $repository->shouldReceive('destroy');
        $this->be($this->user());
        $this->session(['link_types.delete.uri' => 'http://localhost']);
        $response = $this->post(route('admin.links.destroy', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::edit
     */
    public function testEditEditable(): void
    {
        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hello Y', 'outward' => 'bye Y', 'name' => 'Test type Y']);

        $linkType = LinkType::where('editable', 1)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.edit', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::edit
     */
    public function testEditNonEditable(): void
    {
        $linkType = LinkType::where('editable', 0)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.edit', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::index
     */
    public function testIndex(): void
    {
        $linkTypes  = LinkType::inRandomOrder()->take(3)->get();
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn($linkTypes);
        $repository->shouldReceive('countJournals')->andReturn(3);
        $this->be($this->user());
        $response = $this->get(route('admin.links.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::show
     */
    public function testShow(): void
    {
        $linkType = LinkType::first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.show', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController::store
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testStore(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $data       = [
            'name'    => 'test ' . random_int(1, 1000),
            'inward'  => 'test inward' . random_int(1, 1000),
            'outward' => 'test outward' . random_int(1, 1000),
        ];
        $repository->shouldReceive('store')->once()->andReturn(LinkType::first());
        $repository->shouldReceive('find')->andReturn(LinkType::first());

        $this->session(['link_types.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController::store
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testStoreRedirect(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $data       = [
            'name'           => 'test ' . random_int(1, 1000),
            'inward'         => 'test inward' . random_int(1, 1000),
            'outward'        => 'test outward' . random_int(1, 1000),
            'create_another' => '1',
        ];
        $repository->shouldReceive('store')->once()->andReturn(new LinkType);
        $this->session(['link_types.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController::update
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdate(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        // create editable link type just in case:
        $linkType = LinkType::create(['editable' => 1, 'inward' => 'helloxz', 'outward' => 'bzyex', 'name' => 'Test tyzpeX']);
        $repository->shouldReceive('update')->once()->andReturn(new $linkType);

        $data = [
            'name'    => 'test ' . random_int(1, 1000),
            'inward'  => 'test inward' . random_int(1, 1000),
            'outward' => 'test outward' . random_int(1, 1000),
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController::update
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdateNonEditable(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $linkType   = LinkType::where('editable', 0)->first();

        $data = [
            'name'           => 'test ' . random_int(1, 1000),
            'inward'         => 'test inward' . random_int(1, 1000),
            'outward'        => 'test outward' . random_int(1, 1000),
            'return_to_edit' => '1',
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController::update
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdateRedirect(): void
    {
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        // create editable link type just in case:
        $linkType = LinkType::create(['editable' => 1, 'inward' => 'healox', 'outward' => 'byaex', 'name' => 'Test tyapeX']);

        $data = [
            'name'           => 'test ' . random_int(1, 1000),
            'inward'         => 'test inward' . random_int(1, 1000),
            'outward'        => 'test outward' . random_int(1, 1000),
            'return_to_edit' => '1',
        ];
        $repository->shouldReceive('update')->once()->andReturn(new $linkType);
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}

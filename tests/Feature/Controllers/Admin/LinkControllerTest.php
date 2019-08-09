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
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class LinkControllerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LinkControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testCreate(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);

        // mock default session stuff
        $this->mockDefaultSession();


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->be($this->user());
        $response = $this->get(route('admin.links.create'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testDeleteEditable(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        // create editable link type just in case:
        $newType = LinkType::create(['editable' => 1, 'inward' => 'hello', 'outward' => 'bye', 'name' => 'Test type']);

        // mock default session stuff
        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $linkType = LinkType::where('editable', 1)->first();
        $another  = LinkType::where('editable', 0)->first();
        $repository->shouldReceive('get')->once()->andReturn(new Collection([$linkType, $another]));
        $repository->shouldReceive('countJournals')->andReturn(2);
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(200);

        $newType->forceDelete();
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testDeleteNonEditable(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);
        $linkType = LinkType::where('editable', 0)->first();

        // mock default session stuff
        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testDestroy(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hellox', 'outward' => 'byex', 'name' => 'Test typeX']);

        $linkType = LinkType::where('editable', 1)->first();
        $repository->shouldReceive('findNull')->andReturn($linkType);
        $repository->shouldReceive('destroy');
        $this->be($this->user());
        $this->session(['link_types.delete.uri' => 'http://localhost']);
        $response = $this->post(route('admin.links.destroy', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testEditEditable(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hello Y', 'outward' => 'bye Y', 'name' => 'Test type Y']);

        $linkType = LinkType::where('editable', 1)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.edit', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testEditNonEditable(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();


        $linkType = LinkType::where('editable', 0)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.edit', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testIndex(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();
        //Preferences::shouldReceive('mark')->atLeast()->once();

        $linkTypes  = LinkType::inRandomOrder()->take(3)->get();
        $repository = $this->mock(LinkTypeRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn($linkTypes);
        $repository->shouldReceive('countJournals')->andReturn(3);
        $this->be($this->user());
        $response = $this->get(route('admin.links.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController
     */
    public function testShow(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $repository->shouldReceive('getJournalLinks')->andReturn(new Collection);
        $this->mockDefaultSession();


        $linkType = LinkType::first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.show', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testStore(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $data = [
            'name'    => sprintf('test %d', $this->randomInt()),
            'inward'  => sprintf('test inward %d', $this->randomInt()),
            'outward' => sprintf('test outward %d', $this->randomInt()),
        ];
        $repository->shouldReceive('store')->once()->andReturn(LinkType::first());
        $repository->shouldReceive('findNull')->andReturn(LinkType::first());

        $this->session(['link_types.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testStoreRedirect(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $data = [
            'name'           => sprintf('test %d', $this->randomInt()),
            'inward'         => sprintf('test inward %d', $this->randomInt()),
            'outward'        => sprintf('test outward %d', $this->randomInt()),
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
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdate(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // create editable link type just in case:
        $linkType = LinkType::create(['editable' => 1, 'inward' => 'helloxz', 'outward' => 'bzyex', 'name' => 'Test tyzpeX']);
        $repository->shouldReceive('update')->once()->andReturn(new $linkType);

        // mock default session stuff
        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'name'    => sprintf('test %d', $this->randomInt()),
            'inward'  => sprintf('test inward %d', $this->randomInt()),
            'outward' => sprintf('test outward %d', $this->randomInt()),
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdateNonEditable(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();
        $linkType = LinkType::where('editable', 0)->first();

        $data = [
            'name'           => sprintf('test %d', $this->randomInt()),
            'inward'         => sprintf('test inward %d', $this->randomInt()),
            'outward'        => sprintf('test outward %d', $this->randomInt()),
            'return_to_edit' => '1',
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Admin\LinkController
     * @covers       \FireflyIII\Http\Requests\LinkTypeFormRequest
     */
    public function testUpdateRedirect(): void
    {
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(LinkTypeRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        // create editable link type just in case:
        $linkType = LinkType::create(['editable' => 1, 'inward' => 'healox', 'outward' => 'byaex', 'name' => 'Test tyapeX']);

        // mock default session stuff
        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = [
            'name'           => sprintf('test %d', $this->randomInt()),
            'inward'         => sprintf('test inward %d', $this->randomInt()),
            'outward'        => sprintf('test outward %d', $this->randomInt()),
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

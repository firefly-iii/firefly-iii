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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\LinkType;
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
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::__construct
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::create
     */
    public function testCreate()
    {

        $this->be($this->user());
        $response = $this->get(route('admin.links.create'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::delete
     */
    public function testDeleteEditable()
    {
        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hello', 'outward' => 'bye', 'name' => 'Test type']);

        $linkType = LinkType::where('editable', 1)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::delete
     */
    public function testDeleteNonEditable()
    {
        $linkType = LinkType::where('editable', 0)->first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.delete', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::destroy
     */
    public function testDestroy()
    {
        // create editable link type just in case:
        LinkType::create(['editable' => 1, 'inward' => 'hellox', 'outward' => 'byex', 'name' => 'Test typeX']);

        $linkType = LinkType::where('editable', 1)->first();
        $this->be($this->user());
        $this->session(['link_types.delete.uri' => 'http://localhost']);
        $response = $this->post(route('admin.links.destroy', [$linkType->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::edit
     */
    public function testEditEditable()
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
    public function testEditNonEditable()
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
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.links.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::show
     */
    public function testShow()
    {
        $linkType = LinkType::first();
        $this->be($this->user());
        $response = $this->get(route('admin.links.show', [$linkType->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::store
     */
    public function testStore()
    {
        $data = [
            'name'    => 'test ' . rand(1, 1000),
            'inward'  => 'test inward' . rand(1, 1000),
            'outward' => 'test outward' . rand(1, 1000),
        ];
        $this->session(['link_types.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::store
     */
    public function testStoreRedirect()
    {
        $data = [
            'name'           => 'test ' . rand(1, 1000),
            'inward'         => 'test inward' . rand(1, 1000),
            'outward'        => 'test outward' . rand(1, 1000),
            'create_another' => '1',
        ];
        $this->session(['link_types.create.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::update
     */
    public function testUpdate()
    {
        // create editable link type just in case:
        $linKType = LinkType::create(['editable' => 1, 'inward' => 'helloxz', 'outward' => 'bzyex', 'name' => 'Test tyzpeX']);


        $data = [
            'name'    => 'test ' . rand(1, 1000),
            'inward'  => 'test inward' . rand(1, 1000),
            'outward' => 'test outward' . rand(1, 1000),
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linKType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::update
     */
    public function testUpdateNonEditable()
    {
        // create editable link type just in case:
        $linkType = LinkType::where('editable', 0)->first();

        $data = [
            'name'           => 'test ' . rand(1, 1000),
            'inward'         => 'test inward' . rand(1, 1000),
            'outward'        => 'test outward' . rand(1, 1000),
            'return_to_edit' => '1',
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\LinkController::update
     */
    public function testUpdateRedirect()
    {
        // create editable link type just in case:
        $linkType = LinkType::create(['editable' => 1, 'inward' => 'healox', 'outward' => 'byaex', 'name' => 'Test tyapeX']);

        $data = [
            'name'           => 'test ' . rand(1, 1000),
            'inward'         => 'test inward' . rand(1, 1000),
            'outward'        => 'test outward' . rand(1, 1000),
            'return_to_edit' => '1',
        ];
        $this->session(['link_types.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('admin.links.update', [$linkType->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}

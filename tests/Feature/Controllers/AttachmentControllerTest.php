<?php
/**
 * AttachmentControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;


use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('attachments.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::destroy
     */
    public function testDestroy()
    {
        $this->session(['attachments.delete.url' => 'http://localhost']);

        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);
        $this->be($this->user());
        $response = $this->post(route('attachments.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::download
     */
    public function testDownload()
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('This is attachment number one.');

        $this->be($this->user());
        $response = $this->get(route('attachments.download', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('This is attachment number one.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('attachments.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::preview
     */
    public function testPreview()
    {
        $this->be($this->user());
        $response = $this->get(route('attachments.preview', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController::update
     */
    public function testUpdate()
    {
        $this->session(['attachments.edit.url' => 'http://localhost']);
        $data = [
            'title'       => 'Some updated title ' . rand(1000, 9999),
            'notes'       => '',
            'description' => '',
        ];

        $this->be($this->user());
        $response = $this->post(route('attachments.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // view should be updated
        $this->be($this->user());
        $response = $this->get(route('attachments.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['title']);
    }


}
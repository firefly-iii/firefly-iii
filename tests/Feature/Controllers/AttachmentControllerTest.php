<?php
/**
 * AttachmentControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class AttachmentControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttachmentControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDelete(): void
    {
        $this->mockDefaultSession();

        // data
        $attachment = $this->getRandomAttachment();

        // mock stuff
        $this->mock(AttachmentRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('attachments.delete', [$attachment->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $attachment = $this->getRandomAttachment();
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);

        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['attachments.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('attachments.destroy', [$attachment->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDownload(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $attachment = $this->getRandomAttachment();
        $repository = $this->mock(AttachmentRepositoryInterface::class);

        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('This is attachment number one.');

        $this->be($this->user());
        $response = $this->get(route('attachments.download', [$attachment->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('This is attachment number one.');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDownloadFail(): void
    {
        $this->mockDefaultSession();

        // mock stuff
        $attachment = $this->getRandomAttachment();
        $repository = $this->mock(AttachmentRepositoryInterface::class);

        $repository->shouldReceive('exists')->once()->andReturn(false);


        Log::warning('The following error is part of a test.');
        $this->be($this->user());
        $response = $this->get(route('attachments.download', [$attachment->id]));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testEdit(): void
    {
        $this->mockDefaultSession();
        $attachRepository = $this->mock(AttachmentRepositoryInterface::class);
        $userRepos        = $this->mock(UserRepositoryInterface::class);
        $attachment       = $this->getRandomAttachment();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $attachRepository->shouldReceive('getNoteText')->andReturn('OK');
        $this->be($this->user());
        $response = $this->get(route('attachments.edit', [$attachment->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $repository->shouldReceive('get')->andReturn(new Collection([Attachment::first()]))->once();
        $repository->shouldReceive('exists')->andReturn(true)->once();

        $this->be($this->user());
        $response = $this->get(route('attachments.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testUpdate(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $attachment = $this->getRandomAttachment();
        $repository->shouldReceive('update')->once();

        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['attachments.edit.uri' => 'http://localhost']);
        $data = [
            'title'       => 'Some updated title ' . $this->randomInt(),
            'notes'       => 'A',
            'description' => 'B',
        ];

        $this->be($this->user());
        $response = $this->post(route('attachments.update', [$attachment->id]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testView(): void
    {
        $attachment = $this->getRandomAttachment();
        $this->mockDefaultSession();

        $repository = $this->mock(AttachmentRepositoryInterface::class);

        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('This is attachment number one.');


        $this->be($this->user());
        $response = $this->get(route('attachments.view', [$attachment->id]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testViewFail(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $attachment = $this->getRandomAttachment();

        $repository->shouldReceive('exists')->once()->andReturn(false);


        $this->be($this->user());
        $response = $this->get(route('attachments.view', [$attachment->id]));
        $response->assertStatus(500);
    }
}

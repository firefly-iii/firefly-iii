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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
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
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDelete(): void
    {
        // mock stuff
        $attachRepository = $this->mock(AttachmentRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $this->be($this->user());
        $response = $this->get(route('attachments.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDestroy(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['attachments.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('attachments.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testDownload(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('This is attachment number one.');
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('attachments.download', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('This is attachment number one.');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\AttachmentController
     * @expectedExceptionMessage Could not find the indicated attachment
     */
    public function testDownloadFail(): void
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(false);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('attachments.download', [1]));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testEdit(): void
    {
        $attachRepository = $this->mock(AttachmentRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $attachRepository->shouldReceive('getNoteText')->andReturn('OK');
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $this->be($this->user());
        $response = $this->get(route('attachments.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testIndex()
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
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
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('update')->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->session(['attachments.edit.uri' => 'http://localhost']);
        $data = [
            'title'       => 'Some updated title ' . random_int(1000, 9999),
            'notes'       => 'A',
            'description' => 'B',
        ];

        $this->be($this->user());
        $response = $this->post(route('attachments.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testView(): void
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('This is attachment number one.');

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $this->be($this->user());
        $response = $this->get(route('attachments.view', [3]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     * @covers \FireflyIII\Http\Controllers\AttachmentController
     */
    public function testViewFail(): void
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(false);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $this->be($this->user());
        $response = $this->get(route('attachments.view', [1]));
        $response->assertStatus(500);
    }
}

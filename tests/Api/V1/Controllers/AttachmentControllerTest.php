<?php
/**
 * AttachmentControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class AttachmentControllerTest
 */
class AttachmentControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get attachment:
        $attachment = $this->user()->attachments()->first();

        // call API
        $response = $this->delete('/api/v1/attachments/' . $attachment->id);
        $response->assertStatus(204);
    }

    /**
     * Download attachment
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testDownload(): void
    {
        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $content    = 'Attachment content ' . random_int(100, 1000);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('exists')->andReturn(true)->once();
        $repository->shouldReceive('getContent')->andReturn($content)->once();

        // get attachment:
        $attachment = $this->user()->attachments()->first();

        // call API
        $response = $this->get('/api/v1/attachments/' . $attachment->id . '/download');

        $response->assertStatus(200);
        $response->assertSee($content);

    }

    /**
     * Download attachment but file doesn't exist.
     *
     * @covers                   \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testDownloadNotExisting(): void
    {
        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $content    = 'Attachment content ' . random_int(100, 1000);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('exists')->andReturn(false)->once();

        // get attachment:
        $attachment = $this->user()->attachments()->first();

        // call API
        $response = $this->get('/api/v1/attachments/' . $attachment->id . '/download');

        $response->assertStatus(500);
        $response->assertSee('Could not find the indicated attachment. The file is no longer there.');
    }

    /**
     * Download attachment but no file uploaded
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testDownloadNotUploaded(): void
    {
        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // create attachment
        $attachment = Attachment::create(
            [
                'user_id'         => $this->user()->id,
                'attachable_id'   => 1,
                'attachable_type' => TransactionJournal::class,
                'md5'             => md5('Hello' . random_int(1, 10000)),
                'filename'        => 'some name',
                'mime'            => 'text/plain',
                'size'            => 5,
                'uploaded'        => false,

            ]
        );

        // call API
        $response = $this->get('/api/v1/attachments/' . $attachment->id . '/download');

        $response->assertStatus(500);
        $response->assertSee('No file has been uploaded for this attachment (yet).');
    }

    /**
     * List all attachments.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testIndex(): void
    {
        // create stuff
        $attachments = factory(Attachment::class, 10)->create();

        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('get')->once()->andReturn($attachments);
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');

        // test API
        $response = $this->get('/api/v1/attachments');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * List one attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testShow(): void
    {
        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();

        // mock stuff:
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');

        // test API
        $response = $this->get('/api/v1/attachments/' . $attachment->id);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'attachments', 'links' => true],]);
        $response->assertSee($attachment->filename); // attachment file name
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Store a new attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     * @covers \FireflyIII\Api\V1\Requests\AttachmentRequest
     */
    public function testStore(): void
    {
        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();

        // mock stuff:
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($attachment);
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('findNull')->once()->andReturn($this->user()->transactionJournals()->find(1));

        // data to submit
        $data = [
            'filename'    => 'Some new att',
            'description' => sprintf('Attempt #%d', random_int(1, 10000)),
            'model'       => TransactionJournal::class,
            'model_id'    => 1,
        ];


        // test API
        $response = $this->post('/api/v1/attachments', $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'attachments', 'links' => true],]);
        $response->assertSee($attachment->filename); // the file name.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update an attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     * @covers \FireflyIII\Api\V1\Requests\AttachmentRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('update')->once()->andReturn($attachment);
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');
        // data to submit
        $data = [
            'filename'    => $attachment->filename,
            'description' => sprintf('Attempt #%d', random_int(1, 10000)),
            'model'       => TransactionJournal::class,
            'model_id'    => 1,
        ];

        // test API
        $response = $this->put('/api/v1/attachments/' . $attachment->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'attachments', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($attachment->description);

    }

    /**
     * Upload file for attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     *
     */
    public function testUpload(): void
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();


        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();
        $content    = 'Hello there';
        // mock helper:
        $helper = $this->mock(AttachmentHelperInterface::class);
        $helper->shouldReceive('saveAttachmentFromApi')->once();

        $response = $this->call('POST', '/api/v1/attachments/' . $attachment->id . '/upload', [], [], [], [], $content);
        //$response = $this->post('/api/v1/attachments/' . $attachment->id . '/upload',$content, ['Accept' => 'application/json']);
        $response->assertStatus(204);
    }
}

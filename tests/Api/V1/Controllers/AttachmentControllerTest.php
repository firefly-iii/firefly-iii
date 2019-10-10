<?php
/**
 * AttachmentControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Api\V1\Controllers;

use Exception;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class AttachmentControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Test show attachment.
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     */
    public function testShow(): void
    {
        $transformer = $this->mock(AttachmentTransformer::class);
        $repository  = $this->mock(AttachmentRepositoryInterface::class);
        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id'         => 1,
                'attributes' => [
                    'file' => 'Test.pdf',
                ],
            ]);
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once()->andReturn([]);

        $attachment = $this->user()->attachments()->inRandomOrder()->first();

        // test API
        $response = $this->get(route('api.v1.attachments.show', [$attachment->id]));
        $response->assertStatus(200);
    }

    /**
     * Store a new attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     * @throws Exception
     */
    public function testStore(): void
    {
        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();

        // mock stuff:
        $repository   = $this->mock(AttachmentRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $transformer  = $this->mock(AttachmentTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $journal = $this->getRandomWithdrawal();
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($attachment);
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');
        $journalRepos->shouldReceive('setUser')->once();

        $journalRepos->shouldReceive('findNull')->once()->andReturn($journal);

        // data to submit
        $data = [
            'filename'    => 'Some new att',
            'description' => sprintf('Attempt #%d', $this->randomInt()),
            'model'       => 'TransactionJournal',
            'model_id'    => $journal->id,
        ];


        // test API
        $response = $this->post(route('api.v1.attachments.store'), $data, ['accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'attachments', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Update an attachment.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AttachmentController
     * @throws Exception
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository  = $this->mock(AttachmentRepositoryInterface::class);
        $transformer = $this->mock(AttachmentTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        /** @var Attachment $attachment */
        $attachment = $this->user()->attachments()->first();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('update')->once()->andReturn($attachment);
        $repository->shouldReceive('getNoteText')->andReturn('Hi There');
        // data to submit
        $data = [
            'filename'    => $attachment->filename,
            'description' => sprintf('Attempt #%d', $this->randomInt()),
            'model'       => 'TransactionJournal',
            'model_id'    => 1,
        ];

        // test API
        $response = $this->put(route('api.v1.attachments.update', [$attachment->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'attachments', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');


    }
}

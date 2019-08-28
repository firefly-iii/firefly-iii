<?php
/**
 * AttachmentTransformerTest.php
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

namespace Tests\Unit\Transformers;

use FireflyIII\Models\Attachment;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class AttachmentTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentTransformerTest extends TestCase
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
     * Test basic transformer
     *
     * @covers \FireflyIII\Transformers\AttachmentTransformer
     */
    public function testBasic(): void
    {
        $repository = $this->mock(AttachmentRepositoryInterface::class);
        $md5        = md5('hello' . $this->randomInt());
        $attachment = Attachment::create(
            [
                'user_id'         => $this->user()->id,
                'attachable_id'   => 1,
                'attachable_type' => TransactionJournal::class,
                'md5'             => $md5,
                'filename'        => 'hello.txt',
                'mime'            => 'text/plain',
                'size'            => 101,
                'uploaded'        => 1,
            ]
        );

        // expected calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getNoteText')->once()->andReturn('I am a note');

        // make transformer
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($attachment);

        // test results
        $this->assertEquals($attachment->id, $result['id']);
        $this->assertEquals('TransactionJournal', $result['attachable_type']);
        $this->assertEquals('I am a note', $result['notes']);

    }

}

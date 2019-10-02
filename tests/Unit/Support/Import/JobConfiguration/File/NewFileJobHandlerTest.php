<?php
/**
 * NewFileJobHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\File;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class NewFileJobHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NewFileJobHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler
     */
    public function testConfigureJob(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'newfile-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'delimiter'   => ',',
            'has-headers' => true,
        ];
        $job->save();

        // make one attachment.
        $att                  = new Attachment;
        $att->filename        = 'configuration_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // mock stuff
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getConfiguration')->andReturn([])->once();
        $repository->shouldReceive('getAttachments')->twice()->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->times(3)->andReturn('{"a": "b"}');
        $repository->shouldReceive('setConfiguration')->withArgs([Mockery::any(), ['file-type' => 'csv']])->once();
        $repository->shouldReceive('setConfiguration')->withArgs([Mockery::any(), ['a' => 'b']])->twice();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'configure-upload'])->once();

        // data for configure job:
        $data = [
            'import_file_type' => 'csv',
        ];

        $handler = new NewFileJobHandler;
        $handler->setImportJob($job);
        try {
            $messages = $handler->configureJob($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $messages);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler
     */
    public function testConfigureJobBadData(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'newfile-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'delimiter'   => ',',
            'has-headers' => true,
        ];
        $job->save();

        // make one attachment.
        $att                  = new Attachment;
        $att->filename        = 'configuration_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // get file:
        $content = file_get_contents(storage_path('build') . '/ebcdic.txt');

        // mock stuff
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->once()->andReturn($content);

        // data for configure job:
        $data = [
            'import_file_type' => 'csv',
        ];

        $handler = new NewFileJobHandler;
        $handler->setImportJob($job);
        try {
            $messages = $handler->configureJob($data);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $messages);
        $this->assertEquals(
            'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
            $messages->first()
        );
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler
     */
    public function testStoreConfiguration(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'newfile-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'delimiter'   => ',',
            'has-headers' => true,
        ];
        $job->save();

        // make one attachment.
        $att                  = new Attachment;
        $att->filename        = 'configuration_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // mock stuff
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->once()->andReturn('{"a": "b"}');
        $repository->shouldReceive('setConfiguration')->withArgs([Mockery::any(), ['a' => 'b']])->once();

        $handler = new NewFileJobHandler;
        $handler->setImportJob($job);

        try {
            $handler->storeConfiguration();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler
     */
    public function testValidateAttachments(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'newfile-x' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'delimiter'   => ',',
            'has-headers' => true,
        ];
        $job->save();

        // make one attachment.
        $att                  = new Attachment;
        $att->filename        = 'import_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // mock stuff
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->once()->andReturn('Hello!');


        $handler = new NewFileJobHandler;
        $handler->setImportJob($job);

        try {
            $result = $handler->validateAttachments();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler
     */
    public function testValidateNotUTF(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'newfile-x' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'delimiter'   => ',',
            'has-headers' => true,
        ];
        $job->save();

        // make one attachment.
        $att                  = new Attachment;
        $att->filename        = 'import_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // get file:
        $content = file_get_contents(storage_path('build') . '/ebcdic.txt');

        // mock stuff
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->once()->andReturn($content);


        $handler = new NewFileJobHandler;
        $handler->setImportJob($job);

        try {
            $result = $handler->validateAttachments();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $result);
        $this->assertEquals(
            'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
            $result->first()
        );
    }

}

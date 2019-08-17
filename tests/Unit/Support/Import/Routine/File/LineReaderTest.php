<?php
/**
 * LineReaderTest.php
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

namespace Tests\Unit\Support\Import\Routine\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Specifics\IngDescription;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\LineReader;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class LineReaderTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LineReaderTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\LineReader
     */
    public function testGetLines(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'linerA' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'specifics'   => [
                'IngDescription' => 1,
                'BadSpecific'    => 1,
            ],
            'has-headers' => true,
            'delimiter'   => ',',
        ];
        $job->save();

        $att                  = new Attachment;
        $att->filename        = 'import_file';
        $att->user_id         = $this->user()->id;
        $att->attachable_id   = $job->id;
        $att->attachable_type = ImportJob::class;
        $att->md5             = md5('hello');
        $att->mime            = 'fake';
        $att->size            = 3;
        $att->save();

        // mock repositories:
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $specific    = $this->mock(IngDescription::class);

        // fake file content:
        $content   = "header1,header2,header3\ncolumn1,column2,column3\nA,B,C";
        $specifics = [['column1', 'column2', 'column3'], ['A', 'B', 'C']];

        // mock calls and returns:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->andReturn($content)->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn($job->configuration);

        // expect the rows to be run throught the specific.
        $specific->shouldReceive('run')->withArgs([$specifics[0]])->andReturn($specifics[0])->once();
        $specific->shouldReceive('run')->withArgs([$specifics[1]])->andReturn($specifics[1])->once();

        $lineReader = new LineReader;
        $lineReader->setImportJob($job);
        try {
            $lines = $lineReader->getLines();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($specifics, $lines);
    }

}

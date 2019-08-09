<?php
/**
 * ConfigureRolesHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\File;


use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Specifics\IngDescription;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler;
use Illuminate\Support\Collection;
use League\Csv\Reader;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ConfigureRolesHandlerTest
 */
class ConfigureRolesHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testConfigurationCompleteBasic(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);


        $config  = [
            'column-count' => 5,
            'column-roles' => [
                0 => 'amount',
                1 => 'description',
                2 => 'note',
                3 => 'foreign-currency-code',
                4 => 'amount_foreign',
            ],
        ];
        $handler = new ConfigureRolesHandler();
        $result  = $handler->configurationComplete($config);
        $this->assertCount(0, $result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testConfigurationCompleteForeign(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $config  = [
            'column-count' => 5,
            'column-roles' => [
                0 => 'amount',
                1 => 'description',
                2 => 'note',
                3 => 'amount_foreign',
                4 => 'sepa-cc',
            ],
        ];
        $handler = new ConfigureRolesHandler();
        $result  = $handler->configurationComplete($config);
        $this->assertCount(1, $result);
        $this->assertEquals(
            'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
            $result->get('error')[0]
        );
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testConfigurationCompleteNoAmount(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $config      = [
            'column-count' => 5,
            'column-roles' => [
                0 => 'sepa-cc',
                1 => 'description',
                2 => 'note',
                3 => 'foreign-currency-code',
                4 => 'amount_foreign',
            ],
        ];
        $handler     = new ConfigureRolesHandler();
        $result      = $handler->configurationComplete($config);
        $this->assertCount(1, $result);
        $this->assertEquals(
            'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
            $result->get('error')[0]
        );
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testConfigureJob(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'role-B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'column-count' => 5,
        ];
        $job->save();

        $data = [
            'role' => [
                0 => 'description',
                1 => 'budget-id',
                2 => 'sepa-cc',
                4 => 'amount', // no column 3.
            ],
            'map'  => [
                0 => '1', // map column 0 (which cannot be mapped anyway)
                1 => '1', // map column 1 (which CAN be mapped)
            ],
        ];

        $expected = [
            'column-count'      => 5,
            'column-roles'      => [
                0 => 'description',
                1 => 'budget-id',
                2 => 'sepa-cc',
                3 => '_ignore', // added column 3
                4 => 'amount',
            ],
            'column-do-mapping' => [false, true, false, false, false],
        ];

        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'ready_to_run']);
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'map']);
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expected]);

        $handler = new ConfigureRolesHandler();
        $handler->setImportJob($job);
        $handler->configureJob($data);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testGetExampleFromLine(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $lines       = [
            ['one', 'two', '', 'three'],
            ['four', 'five', '', 'six'],
        ];

        $handler = new ConfigureRolesHandler;
        foreach ($lines as $line) {
            $handler->getExampleFromLine($line);
        }
        $expected = [
            0 => ['one', 'four'],
            1 => ['two', 'five'],
            3 => ['three', 'six'],
        ];
        $this->assertEquals($expected, $handler->getExamples());
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testGetExamplesFromFile(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $importRepos->shouldReceive('setConfiguration')->once()
                    ->withAnyArgs();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'role-x' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'specifics'   => [],
            'has-headers' => false,
        ];
        $job->save();

        $file    = "one,two,,three\nfour,five,,six\none,three,X,three";
        $reader  = Reader::createFromString($file);
        $handler = new ConfigureRolesHandler;
        $handler->setImportJob($job);
        try {
            $handler->getExamplesFromFile($reader, $job->configuration);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $expected = [
            0 => ['one', 'four'],
            1 => ['two', 'five', 'three'],
            2 => ['X'],
            3 => ['three', 'six'],
        ];
        $this->assertEquals($expected, $handler->getExamples());

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testGetHeadersHas(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        //$importRepos->shouldReceive('setUser')->once();
        // create a reader to use in method.
        // 5 columns, of which #4 (index 3) is budget-id
        // 5 columns, of which #5 (index 4) is tags-space
        $file   = "header1,header2,header3,header4,header5\nvalue4,value5,value6,2,more tags there\nvalueX,valueY,valueZ\nA,B,C,,\nd,e,f,1,xxx";
        $reader = Reader::createFromString($file);
        $config = ['has-headers' => true];

        $handler = new ConfigureRolesHandler;
        try {
            $headers = $handler->getHeaders($reader, $config);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['header1', 'header2', 'header3', 'header4', 'header5'], $headers);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testGetHeadersNone(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        // create a reader to use in method.
        // 5 columns, of which #4 (index 3) is budget-id
        // 5 columns, of which #5 (index 4) is tags-space
        $file   = "header1,header2,header3,header4,header5\nvalue4,value5,value6,2,more tags there\nvalueX,valueY,valueZ\nA,B,C,,\nd,e,f,1,xxx";
        $reader = Reader::createFromString($file);
        $config = ['has-headers' => false];

        $handler = new ConfigureRolesHandler;
        try {
            $headers = $handler->getHeaders($reader, $config);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals([], $headers);
    }

    public function testGetNextData(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'role-x' . $this->randomInt();
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

        $fileContent = "column1,column2,column3\nvalue1,value2,value3";
        // mock some helpers:
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn($job->configuration);
        $repository->shouldReceive('setConfiguration')->once()->withArgs(
            [Mockery::any(),
             [
                 'delimiter'    => ',',
                 'has-headers'  => true,
                 'column-count' => 3,
             ],
            ]
        );
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->withArgs([Mockery::any()])->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->withArgs([Mockery::any()])->andReturn($fileContent);

        $expected = [
            'examples' => [
                0 => ['value1'],
                1 => ['value2'],
                2 => ['value3'],
            ],
            'total'    => 3,
            'headers'  => ['column1', 'column2', 'column3'],
        ];

        $handler = new ConfigureRolesHandler();
        $handler->setImportJob($job);
        try {
            $result = $handler->getNextData();
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($expected['examples'], $result['examples']);
        $this->assertEquals($expected['total'], $result['total']);
        $this->assertEquals($expected['headers'], $result['headers']);

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testGetReader(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'role-x' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
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
        $config = [
            'delimiter' => ',',
        ];

        $fileContent = "column1,column2,column3\nvalue1,value2,value3";

        // mock some helpers:
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn($config);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->withArgs([Mockery::any()])->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->withArgs([Mockery::any()])->andReturn($fileContent);

        $handler = new ConfigureRolesHandler();
        $handler->setImportJob($job);
        try {
            $reader = $handler->getReader();
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testIgnoreUnmappableColumns(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $config   = [
            'column-count'      => 5,
            'column-roles'      => [
                'description', // cannot be mapped.
                'budget-id',
                'sepa-cc',     // cannot be mapped.
                'category-id',
                'tags-comma',  // cannot be mapped.
            ],
            'column-do-mapping' => [
                0 => true,
                1 => true,
                2 => true,
                3 => true,
                4 => true,
            ],
        ];
        $expected = [
            'column-count'      => 5,
            'column-roles'      => [
                'description', // cannot be mapped.
                'budget-id',
                'sepa-cc',     // cannot be mapped.
                'category-id',
                'tags-comma',  // cannot be mapped.
            ],
            'column-do-mapping' => [
                0 => false,
                1 => true,
                2 => false,
                3 => true,
                4 => false,
            ],
        ];
        $handler  = new ConfigureRolesHandler;
        $this->assertEquals($expected, $handler->ignoreUnmappableColumns($config));
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testIsMappingNecessaryNo(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $config  = [
            'column-do-mapping' => [false, false, false],
        ];
        $handler = new ConfigureRolesHandler();
        $result  = $handler->isMappingNecessary($config);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testIsMappingNecessaryYes(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $config  = [
            'column-do-mapping' => [false, true, false, false],
        ];
        $handler = new ConfigureRolesHandler();
        $result  = $handler->isMappingNecessary($config);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testMakeExamplesUnique(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $lines = [
            ['one', 'two', '', 'three'],
            ['four', 'five', '', 'six'],
            ['one', 'three', 'X', 'three'],
        ];

        $handler = new ConfigureRolesHandler;
        foreach ($lines as $line) {
            $handler->getExampleFromLine($line);
        }
        $handler->makeExamplesUnique();

        $expected = [
            0 => ['one', 'four'],
            1 => ['two', 'five', 'three'],
            2 => ['X'],
            3 => ['three', 'six'],
        ];
        $this->assertEquals($expected, $handler->getExamples());
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testProcessSpecifics(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);

        $line   = [];
        $config = [
            'specifics' => [
                'IngDescription'    => true,
                'some-bad-specific' => true,
            ],
        ];

        $ingDescription = $this->mock(IngDescription::class);
        $ingDescription->shouldReceive('run')->once()->withArgs([[]])->andReturn(['a' => 'b']);

        $handler = new ConfigureRolesHandler;
        $this->assertEquals(['a' => 'b'], $handler->processSpecifics($config, []));

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler
     */
    public function testSaveColumCount(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'role-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('setConfiguration')->once()
                   ->withArgs([Mockery::any(), ['column-count' => 0]]);

        $handler = new ConfigureRolesHandler();
        $handler->setImportJob($job);
        $handler->saveColumCount();
    }

}

<?php
/**
 * ConfigureMappingHandlerTest.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Mapper\Budgets;
use FireflyIII\Import\MapperPreProcess\TagsSpace;
use FireflyIII\Import\Specifics\IngDescription;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler;
use Illuminate\Support\Collection;
use League\Csv\Exception;
use League\Csv\Reader;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ConfigureMappingHandlerTest
 *
 */
class ConfigureMappingHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testApplySpecifics(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapG' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $expected = ['a' => 'ING'];

        // mock ING description (see below)
        $ingDescr = $this->mock(IngDescription::class);
        $ingDescr->shouldReceive('run')->once()->andReturn($expected);

        $config = [
            'specifics' => [
                'IngDescription' => 1,
                'bad-specific'   => 1,
            ],
        ];

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        $result = $handler->applySpecifics($config, []);
        $this->assertEquals($expected, $result);

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testConfigureJob(): void
    {

        // create fake input for class method:
        $input          = [
            'mapping' => [

                0 => [// column
                      'fake-iban'        => 1,
                      'other-fake-value' => '2', // string
                ],
                1 => [
                    3                  => 2, // fake number
                    'final-fake-value' => 3,
                    'mapped-to-zero'   => 0,
                ],

            ],
        ];
        $expectedResult = [
            'column-mapping-config' =>
                [
                    0 => [
                        'fake-iban'        => 1,
                        'other-fake-value' => 2,
                    ],
                    1 => [
                        '3'                => 2,
                        'final-fake-value' => 3,
                    ],
                ],

        ];


        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapA' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();


        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // run configure mapping handler.
        // expect specific results:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'ready_to_run']);
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedResult]);


        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        $handler->configureJob($input);

    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testDoColumnConfig(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapE' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $fakeBudgets = [
            0 => 'dont map',
            1 => 'Fake budget A',
            4 => 'Other fake budget',
        ];

        // fake budget mapper (see below)
        $budgetMapper = $this->mock(Budgets::class);
        $budgetMapper->shouldReceive('getMap')->once()->andReturn($fakeBudgets);

        // input array:
        $input = [
            'column-roles'      => [
                0 => 'description', // cannot be mapped
                1 => 'sepa-ct-id', // cannot be mapped
                2 => 'tags-space', // cannot be mapped, has a pre-processor.
                3 => 'account-id', // can be mapped
                4 => 'budget-id' // can be mapped.
            ],
            'column-do-mapping' => [
                0 => false, // don't try to map description
                1 => true,  // try to map sepa (cannot)
                2 => true,  // try to map tags (cannot)
                3 => false, // dont map mappable
                4 => true, // want to map, AND can map.
            ],
        ];

        $expected = [
            4 => [
                'name'          => 'budget-id',
                'options'       => $fakeBudgets,
                'preProcessMap' => '',
                'values'        => [],
            ],
        ];

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        try {
            $result = $handler->doColumnConfig($input);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testDoMapOfColumn(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapC' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $combinations = [
            ['role' => 'description', 'expected' => false, 'requested' => false], // description cannot be mapped. Will always return false.
            ['role' => 'description', 'expected' => false, 'requested' => true], // description cannot be mapped. Will always return false.
            ['role' => 'currency-id', 'expected' => false, 'requested' => false], // if not requested, return false.
            ['role' => 'currency-id', 'expected' => true, 'requested' => true], // if requested, return true.
        ];

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        foreach ($combinations as $info) {
            $this->assertEquals($info['expected'], $handler->doMapOfColumn($info['role'], $info['requested']));
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testGetNextData(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapH' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'column-roles'      => [
                0 => 'description', // cannot be mapped
                1 => 'sepa-ct-id', // cannot be mapped
                2 => 'tags-space', // cannot be mapped, has a pre-processor.
                3 => 'account-id', // can be mapped
                4 => 'budget-id' // can be mapped.
            ],
            'column-do-mapping' => [
                0 => false, // don't try to map description
                1 => true,  // try to map sepa (cannot)
                2 => true,  // try to map tags (cannot)
                3 => false, // dont map mappable
                4 => true, // want to map, AND can map.
            ],
            'delimiter'         => ',',
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

        // fake some data.
        $fileContent = "column1,column2,column3,column4,column5\nvalue1,value2,value3,value4,value5";
        $fakeBudgets = [
            0 => 'dont map',
            1 => 'Fake budget A',
            4 => 'Other fake budget',
        ];
        // mock some helpers:
        $attachments = $this->mock(AttachmentHelperInterface::class);
        $repository  = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('getConfiguration')->once()->withArgs([Mockery::any()])->andReturn($job->configuration);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAttachments')->once()->withArgs([Mockery::any()])->andReturn(new Collection([$att]));
        $attachments->shouldReceive('getAttachmentContent')->withArgs([Mockery::any()])->andReturn($fileContent);
        $budgetMapper = $this->mock(Budgets::class);
        $budgetMapper->shouldReceive('getMap')->once()->andReturn($fakeBudgets);


        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        try {
            $result = $handler->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $expected = [
            4 => [ // is the one with the budget id, remember?
                   'name'          => 'budget-id',
                   'options'       => $fakeBudgets,
                   'preProcessMap' => '',
                   'values'        => ['column5', 'value5'], // see $fileContent
            ],
        ];

        $this->assertEquals($expected, $result);


    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testGetPreProcessorName(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapD' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $combinations = [
            ['role' => 'tags-space', 'expected' => TagsSpace::class], // tags- space has a pre-processor. Return it.
            ['role' => 'description', 'expected' => ''], // description has not.
            ['role' => 'no-such-role', 'expected' => ''], // not existing role has not.
        ];

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        foreach ($combinations as $info) {
            $this->assertEquals($info['expected'], $handler->getPreProcessorName($info['role']));
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testGetReader(): void
    {

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapF' . $this->randomInt();
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

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        try {
            $reader = $handler->getReader();
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testGetValuesForMapping(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        // create a reader to use in method.
        // 5 columns, of which #4 (index 3) is budget-id
        // 5 columns, of which #5 (index 4) is tags-space
        $file   = "value1,value2,value3,1,some tags here\nvalue4,value5,value6,2,more tags there\nvalueX,valueY,valueZ\nA,B,C,,\nd,e,f,1,xxx";
        $reader = Reader::createFromString($file);

        // make config for use in method.
        $config = [
            'has-headers' => false,
        ];

        // make column config
        $columnConfig = [
            3 => [
                'name'          => 'budget-id',
                'options'       => [
                    0 => 'dont map',
                    1 => 'Fake budget A',
                    4 => 'Other fake budget',
                ],
                'preProcessMap' => '',
                'values'        => [],
            ],
        ];

        // expected result
        $expected = [
            3 => [
                'name'          => 'budget-id',
                'options'       => [
                    0 => 'dont map',
                    1 => 'Fake budget A',
                    4 => 'Other fake budget',
                ],
                'preProcessMap' => '',
                'values'        => ['1', '2'] // all values from column 3 of "CSV" file, minus double values
            ],
        ];

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapB' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        $result = [];
        try {
            $result = $handler->getValuesForMapping($reader, $config, $columnConfig);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($expected, $result);


    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler
     */
    public function testSanitizeColumnName(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $importRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'mapB' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $handler = new ConfigureMappingHandler;
        $handler->setImportJob($job);
        $keys = array_keys(config('csv.import_roles'));
        foreach ($keys as $key) {
            $this->assertEquals($key, $handler->sanitizeColumnName($key));
        }
        $this->assertEquals('_ignore', $handler->sanitizeColumnName('some-bad-name'));
    }

}

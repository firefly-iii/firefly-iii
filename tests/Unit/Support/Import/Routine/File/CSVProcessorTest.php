<?php
/**
 * CSVProcessorTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Unit\Support\Import\Routine\File;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\Routine\File\CSVProcessor;
use FireflyIII\Support\Import\Routine\File\ImportableConverter;
use FireflyIII\Support\Import\Routine\File\ImportableCreator;
use FireflyIII\Support\Import\Routine\File\LineReader;
use FireflyIII\Support\Import\Routine\File\MappedValuesValidator;
use FireflyIII\Support\Import\Routine\File\MappingConverger;
use Log;
use Tests\TestCase;

/**
 * Do some end to end testing here, perhaps?
 *
 * Class CSVProcessorTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CSVProcessorTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\CSVProcessor
     */
    public function testBasic(): void
    {

        // mock all classes:
        $lineReader = $this->mock(LineReader::class);
        $lineReader->shouldReceive('setImportJob')->once();
        $lineReader->shouldReceive('getLines')->once()->andReturn([]);

        $mappingConverger = $this->mock(MappingConverger::class);
        $mappingConverger->shouldReceive('setImportJob')->once();
        $mappingConverger->shouldReceive('converge')->withArgs([[]])->andReturn([])->once();
        $mappingConverger->shouldReceive('getMappedValues')->andReturn([])->once();


        $validator = $this->mock(MappedValuesValidator::class);
        $validator->shouldReceive('setImportJob')->once();
        $validator->shouldReceive('validate')->andReturn([]);

        $creator = $this->mock(ImportableCreator::class);
        $creator->shouldReceive('convertSets')->withArgs([[]])->andReturn([])->once();

        $converter = $this->mock(ImportableConverter::class);
        $converter->shouldReceive('setImportJob')->once();
        $converter->shouldReceive('setMappedValues')->once()->withArgs([[]])->andReturn([]);
        $converter->shouldReceive('convert')->withArgs([[]])->once()->andReturn([]);


        /** @var ImportJob $job */
        $job       = $this->user()->importJobs()->first();
        $processor = new CSVProcessor;
        $processor->setImportJob($job);
        try {
            $result = $processor->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals([], $result);
    }
}

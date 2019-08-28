<?php
/**
 * MappingConvergerTest.php
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
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\MappingConverger;
use Log;
use Tests\TestCase;

/**
 * Class MappingConvergerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MappingConvergerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\MappingConverger
     */
    public function testConverge(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        // configuration
        $config = [
            'column-roles'          => [
                0 => 'account-name',
                1 => 'bill-name',
                2 => 'budget-name',
                3 => 'currency-code',
                4 => 'category-name',
                5 => 'foreign-currency-code',
                6 => 'opposing-number',
                7 => 'description',
                8 => 'opposing-iban',
            ],
            'column-mapping-config' => [
                0 => [
                    'Checking Account' => 1,
                ],
                1 => [
                    'BillX' => 2,
                ],
                2 => [
                    'BudgetX' => 2,
                ],
                3 => [
                    'EUR' => 7,
                ],
                4 => [
                    'CategoryX' => 2,
                ],
                5 => [
                    'USD' => 4,
                ],
                6 => [
                    'SomeNumber' => 3,
                ],
                8 => [
                    'IBANX' => 2,
                ],
            ],
            'column-do-mapping'     => [
                0 => true,
                1 => true,
                2 => true,
                3 => true,
                4 => true,
                5 => true,
                6 => true,
                7 => false,
                8 => false,
            ],
        ];

        // just one line to process (should hit everything).
        $lines = [
            [
                0 => 'Checking Account',
                1 => 'BillX',
                2 => 'BudgetX',
                3 => 'EUR',
                4 => 'CategoryX',
                5 => 'USD',
                6 => 'SomeNumber',
                7 => 'I am a description',
                8 => 'IBANX',
            ],
            [
                0 => 'CheckingX Account',
                1 => 'BillXA',
                2 => 'BudgetBX',
                3 => 'EUD',
                4 => 'CategoryX',
                5 => 'USA',
                6 => 'SomeANumber',
                7 => 'I am X description',
                8 => 'IBANXX',
            ],
        ];

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'linerB' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = $config;
        $job->save();


        $converger = new MappingConverger;
        $converger->setImportJob($job);
        try {
            $result = $converger->converge($lines);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        // do some comparing, we know what to expect.

        // line 0, column 0 is the account name.
        $this->assertEquals($lines[0][0], $result[0][0]->getValue());
        $this->assertEquals($config['column-roles'][0], $result[0][0]->getOriginalRole());
        $this->assertEquals('account-id', $result[0][0]->getRole()); // role changed due to mapping.
        $this->assertEquals(1, $result[0][0]->getMappedValue()); // can see which value it was given.

        // line 1, column 0 is the account name, but it could not be mapped.
        $this->assertEquals($lines[1][0], $result[1][0]->getValue());
        $this->assertEquals($config['column-roles'][0], $result[1][0]->getOriginalRole());
        $this->assertEquals($config['column-roles'][0], $result[1][0]->getRole()); // role did not change, no mapping.
        $this->assertEquals(0, $result[1][0]->getMappedValue()); // value of mapping is 0.
    }

}

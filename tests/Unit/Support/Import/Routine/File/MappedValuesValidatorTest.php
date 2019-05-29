<?php
/**
 * MappedValuesValidatorTest.php
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\MappedValuesValidator;
use Illuminate\Support\Collection;
use Log;
use stdClass;
use Tests\TestCase;

/**
 * Class MappedValuesValidatorTest
 */
class MappedValuesValidatorTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\MappedValuesValidator
     */
    public function testValidateBasic(): void
    {

        $toValidate = [
            'opposing-id'         => [1, 2, 3],
            'account-id'          => [4, 5, 6],
            'currency-id'         => [7, 8, 9],
            'foreign-currency-id' => [10, 11, 12],
            'bill-id'             => [13, 14, 15],
            'budget-id'           => [16, 17, 18],
            'category-id'         => [19, 20, 21],
        ];
        $return     = [
            'opposing-id'         => new Collection([$this->objectWithId(1), $this->objectWithId(2)]),
            'account-id'          => new Collection([$this->objectWithId(4), $this->objectWithId(5)]),
            'currency-id'         => new Collection([$this->objectWithId(7), $this->objectWithId(9)]),
            'foreign-currency-id' => new Collection([$this->objectWithId(10), $this->objectWithId(11)]),
            'bill-id'             => new Collection([$this->objectWithId(13), $this->objectWithId(15)]),
            'budget-id'           => new Collection([$this->objectWithId(16), $this->objectWithId(17)]),
            'category-id'         => new Collection([$this->objectWithId(19), $this->objectWithId(21)]),
        ];
        // mock stuff:
        $repository    = $this->mock(ImportJobRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $billRepos     = $this->mock(BillRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $catRepos      = $this->mock(CategoryRepositoryInterface::class);

        // should receive a lot of stuff:
        $repository->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $billRepos->shouldReceive('setUser')->once();
        $budgetRepos->shouldReceive('setUser')->once();
        $catRepos->shouldReceive('setUser')->once();

        $accountRepos->shouldReceive('getAccountsById')->once()->withArgs([$toValidate['account-id']])->andReturn($return['account-id']);
        $accountRepos->shouldReceive('getAccountsById')->once()->withArgs([$toValidate['opposing-id']])->andReturn($return['opposing-id']);
        $currencyRepos->shouldReceive('getByIds')->once()->withArgs([$toValidate['currency-id']])->andReturn($return['currency-id']);
        $currencyRepos->shouldReceive('getByIds')->once()->withArgs([$toValidate['foreign-currency-id']])->andReturn($return['foreign-currency-id']);
        $billRepos->shouldReceive('getByIds')->once()->withArgs([$toValidate['bill-id']])->andReturn($return['bill-id']);
        $budgetRepos->shouldReceive('getByIds')->once()->withArgs([$toValidate['budget-id']])->andReturn($return['budget-id']);
        $catRepos->shouldReceive('getByIds')->once()->withArgs([$toValidate['category-id']])->andReturn($return['category-id']);


        $expected  = [
            'opposing-id'         => [1, 2],
            'account-id'          => [4, 5],
            'currency-id'         => [7, 9],
            'foreign-currency-id' => [10, 11],
            'bill-id'             => [13, 15],
            'budget-id'           => [16, 17],
            'category-id'         => [19, 21],
        ];
        $validator = new MappedValuesValidator;
        $validator->setImportJob($this->user()->importJobs()->first());

        try {
            $result = $validator->validate($toValidate);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($expected, $result);

    }

    /**
     * @param int $id
     *
     * @return stdClass
     */
    private function objectWithId(int $id): stdClass
    {
        $obj     = new stdClass();
        $obj->id = $id;

        return $obj;
    }

}

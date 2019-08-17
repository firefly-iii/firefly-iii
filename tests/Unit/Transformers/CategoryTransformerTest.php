<?php
/**
 * CategoryTransformerTest.php
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

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Transformers\CategoryTransformer;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;


/**
 * Class CategoryTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryTransformerTest extends TestCase
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
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\CategoryTransformer
     */
    public function testBasic(): void
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        /** @var Category $category */
        $category    = Category::first();
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($category);

        $this->assertEquals($category->name, $result['name']);
        $this->assertEquals([], $result['spent']);
        $this->assertEquals([], $result['earned']);
    }

    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\CategoryTransformer
     */
    public function testWithDates(): void
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        $parameters = new ParameterBag;
        $parameters->set('start', new Carbon('2018-01-01'));
        $parameters->set('end', new Carbon('2018-01-31'));

        // mock some objects for the spent/earned lists.
        $expense                            = new Transaction;
        $expense->transaction_currency_code = 'EUR';
        $expense->transactionCurrency       = $this->getEuro();
        $expense->transaction_amount        = '-100';
        $income                             = new Transaction;
        $income->transaction_currency_code  = 'EUR';
        $income->transactionCurrency        = $this->getEuro();
        $income->transaction_amount         = '100';


        $incomeCollection  = [$income];
        $expenseCollection = [$expense];

        $repository->shouldReceive('spentInPeriodCollection')->atLeast()->once()->andReturn($expenseCollection);
        $repository->shouldReceive('earnedInPeriodCollection')->atLeast()->once()->andReturn($incomeCollection);

        /** @var Category $category */
        $category    = Category::first();
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($parameters);
        $result = $transformer->transform($category);

        $this->assertEquals($category->name, $result['name']);
        $this->assertEquals(
            [
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'EUR',
                    'currency_symbol'         => '€',
                    'currency_decimal_places' => 2,
                    'amount'                  => -100,
                ],
            ], $result['spent']
        );
        $this->assertEquals(
            [
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'EUR',
                    'currency_symbol'         => '€',
                    'currency_decimal_places' => 2,
                    'amount'                  => 100,
                ],
            ], $result['earned']
        );
    }
}

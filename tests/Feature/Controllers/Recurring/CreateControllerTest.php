<?php
/**
 * CreateControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;


use Carbon\Carbon;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class CreateControllerTest
 */
class CreateControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     */
    public function testCreate(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        \Amount::shouldReceive('getDefaultCurrency')->andReturn(TransactionCurrency::find(1));


        $this->be($this->user());
        $response = $this->get(route('recurring.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStore(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);

        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreYearly(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);

        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'yearly,2018-01-01',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreMonthly(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);

        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'monthly,5',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreNdom(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);

        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'ndom,3,5',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreDeposit(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);


        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'deposit',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '2',
            'source_name'             => 'Some source',
            'destination_id'          => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreTransfer(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);


        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'transfer',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '2',
            'source_name'             => 'Some source',
            'destination_id'          => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'times',
            'repetitions'             => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreUntilDate(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $categoryRepos  = $this->mock(CategoryRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos     = $this->mock(PiggyBankRepositoryInterface::class);


        $tomorrow       = Carbon::create()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();
        $data           = [
            'title'                   => 'hello' . random_int(1, 100000),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . random_int(1, 100000),
            'active'                  => '1',
            'apply_rules'             => '1',
            'foreign_amount'          => '1',
            'foreign_currency_id'     => '2',

            // mandatory for transaction:
            'transaction_description' => 'Some descr',
            'transaction_type'        => 'withdrawal',
            'transaction_currency_id' => '1',
            'amount'                  => '30',

            // mandatory account info:
            'source_id'               => '1',
            'destination_name'        => 'Some Expense',

            // optional fields:
            'budget_id'               => '1',
            'category'                => 'CategoryA',
            'tags'                    => 'A,B,C',
            'create_another'          => '1',
            'repetition_end'          => 'until_date',
            'repeat_until'            => $tomorrow->format('Y-m-d'),
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
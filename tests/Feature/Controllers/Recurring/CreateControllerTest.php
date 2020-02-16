<?php
/**
 * CreateControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;


use Amount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 *
 * Class CreateControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     */
    public function testCreate(): void
    {
        // mock repositories, even if not used.
        $this->mock(RecurringRepositoryInterface::class);
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(PiggyBankRepositoryInterface::class);

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $euro  = $this->getEuro();
        $asset = $this->getRandomAsset();
        $cash  = $this->getRandomAsset();
        $this->mockDefaultSession();


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();


        // for view:
        $accountRepos->shouldReceive('getActiveAccountsByType')->atLeast()->once()->andReturn(new Collection([$asset]));
        Steam::shouldReceive('balance')->andReturn('100')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('getCashAccount')->atLeast()->once()->andReturn($cash);
        //Amount::shouldReceive('getDefaultCurrency')->andReturn($euro)->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('100');


        $this->be($this->user());
        $response = $this->get(route('recurring.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('source_id_holder');
        $response->assertSee('deposit_source_id');
        $response->assertSee('withdrawal_destination_id');

    }

    /**
     * Stores a withdrawal. From Asset account to Expense account
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreWithdrawalExpense(): void
    {
        // mock repositories, even if not used.
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);


        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


    /**
     * Stores a withdrawal, but destination is invalid
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreWithdrawalInvalidDest(): void
    {
        // mock repositories, even if not used.
        $this->mock(BudgetRepositoryInterface::class);

        $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);

        $this->mockDefaultSession();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(false);


        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('errors');
    }

    /**
     * Try to store withdrawal, but the source account is invalid.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreWithdrawalInvalidSource(): void
    {
        $this->mockDefaultSession();
        // mock repositories, even if not used.
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);


        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        // source account is invalid.
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(false);

        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('errors');
    }

    /**
     * Stores a withdrawal. But throw error.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreError(): void
    {
        // mock repositories, even if not used.
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);

        $this->mockDefaultSession();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $recurringRepos->shouldReceive('store')->andThrow(new FireflyException('Some exception'));

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Some exception');
    }

    /**
     * Store a deposit from Revenue to Asset.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreDepositRevenue(): void
    {
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomRevenue();
        $destination    = $this->getRandomAsset();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['deposit'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $tomorrow   = Carbon::now()->addDays(2);
        $recurrence = $this->user()->recurrences()->first();
        $data       = [
            'title'                   => 'hello' . $this->randomInt(),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . $this->randomInt(),
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
            'deposit_source_id'       => $source->id,
            'destination_id'          => $destination->id,

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
     * Store a withdrawal but it's monthly, not daily.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreMonthly(): void
    {
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'monthly,5',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * Store a withdrawal but use ndom.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\CreateController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testStoreNdom(): void
    {
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'ndom,3,5',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
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
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomAsset($source->id);

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['transfer'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);


        $tomorrow   = Carbon::now()->addDays(2);
        $recurrence = $this->user()->recurrences()->first();
        $data       = [
            'title'                   => 'hello' . $this->randomInt(),
            'first_date'              => $tomorrow->format('Y-m-d'),
            'repetition_type'         => 'daily',
            'skip'                    => 0,
            'recurring_description'   => 'Some descr' . $this->randomInt(),
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
            'source_id'               => $source->id,
            'destination_id'          => $destination->id,

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
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $tomorrow   = Carbon::now()->addDays(2);
        $recurrence = $this->user()->recurrences()->first();
        $data       = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'until_date',
            'repeat_until'              => $tomorrow->format('Y-m-d'),
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
        $this->mock(BudgetRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $source         = $this->getRandomAsset();
        $destination    = $this->getRandomExpense();
        $tomorrow       = Carbon::now()->addDays(2);
        $recurrence     = $this->user()->recurrences()->first();

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);

        $data = [
            'title'                     => sprintf('hello %d', $this->randomInt()),
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'yearly,2018-01-01',
            'skip'                      => 0,
            'recurring_description'     => sprintf('Some descr %d', $this->randomInt()),
            'active'                    => '1',
            'apply_rules'               => '1',
            'foreign_amount'            => '1',
            'foreign_currency_id'       => '2',

            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',

            // mandatory account info:
            'source_id'                 => $source->id,
            'withdrawal_destination_id' => $destination->id,

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];

        $recurringRepos->shouldReceive('store')->andReturn($recurrence)->once();

        $this->be($this->user());
        $response = $this->post(route('recurring.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}

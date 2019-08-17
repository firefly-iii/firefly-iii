<?php
/**
 * EditControllerTest.php
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

use Amount;
use Carbon\Carbon;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 *
 * Class EditControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EditControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Recurring\EditController
     */
    public function testEdit(): void
    {
        $this->mock(CurrencyRepositoryInterface::class);
        $this->mock(PiggyBankRepositoryInterface::class);

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos    = $this->mock(BudgetRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $transformer    = $this->mock(RecurrenceTransformer::class);
        $asset          = $this->getRandomAsset();
        $euro           = $this->getEuro();
        $cash           = $this->getRandomAsset();
        $this->mockDefaultSession();

        $transformed = [
            'transactions' => [
                [
                    'source_id'      => 1,
                    'destination_id' => 1,
                ],
            ],
        ];

        // for view:
        $accountRepos->shouldReceive('getActiveAccountsByType')->atLeast()->once()->andReturn(new Collection([$asset]));
        Steam::shouldReceive('balance')->andReturn('100')->atLeast()->once();
        $accountRepos->shouldReceive('getAccountCurrency')->atLeast()->once()->andReturn($euro);
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturnNull();
        $accountRepos->shouldReceive('getCashAccount')->atLeast()->once()->andReturn($cash);
        //Amount::shouldReceive('getDefaultCurrency')->andReturn($euro)->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('100');

        // transform recurrence.
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn($transformed);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // get stuff from recurrence.
        $recurringRepos->shouldReceive('setUser');
        $recurringRepos->shouldReceive('getNoteText')->andReturn('Note!');
        $recurringRepos->shouldReceive('repetitionDescription')->andReturn('dunno');
        $recurringRepos->shouldReceive('getXOccurrences')->andReturn([]);
        $budgetRepos->shouldReceive('findNull')->andReturn($this->user()->budgets()->first());


        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        //\Amount::shouldReceive('getDefaultCurrency')->andReturn($this->getEuro());


        $this->be($this->user());
        $response = $this->get(route('recurring.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee('deposit_source_id');
        $response->assertSee('withdrawal_destination_id');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\EditController
     * @covers \FireflyIII\Http\Requests\RecurrenceFormRequest
     */
    public function testUpdate(): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $validator      = $this->mock(AccountValidator::class);
        $expense        = $this->getRandomExpense();

        $this->mockDefaultSession();

        $recurringRepos->shouldReceive('update')->once();

        // validator:
        $validator->shouldReceive('setTransactionType')->withArgs(['withdrawal'])->atLeast()->once();
        $validator->shouldReceive('validateSource')->atLeast()->once()->andReturn(true);
        $validator->shouldReceive('validateDestination')->atLeast()->once()->andReturn(true);
        Preferences::shouldReceive('mark')->once();

        $tomorrow   = Carbon::now()->addDays(2);
        $recurrence = $this->user()->recurrences()->first();
        $data       = [
            'id'                        => $recurrence->id,
            'title'                     => 'hello',
            'first_date'                => $tomorrow->format('Y-m-d'),
            'repetition_type'           => 'daily',
            'skip'                      => 0,
            'recurring_description'     => 'Some descr',
            'active'                    => '1',
            'apply_rules'               => '1',
            'return_to_edit'            => '1',
            // mandatory for transaction:
            'transaction_description'   => 'Some descr',
            'transaction_type'          => 'withdrawal',
            'transaction_currency_id'   => '1',
            'amount'                    => '30',
            // mandatory account info:
            'source_id'                 => '1',
            'source_name'               => '',
            'withdrawal_destination_id' => $expense->id,
            'destination_id'            => '',
            'destination_name'          => 'Some Expense',

            // optional fields:
            'budget_id'                 => '1',
            'category'                  => 'CategoryA',
            'tags'                      => 'A,B,C',
            'create_another'            => '1',
            'repetition_end'            => 'times',
            'repetitions'               => 3,
        ];


        $this->be($this->user());
        $response = $this->post(route('recurring.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}

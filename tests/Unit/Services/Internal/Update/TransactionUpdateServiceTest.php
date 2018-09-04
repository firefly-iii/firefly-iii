<?php
/**
 * TransactionUpdateServiceTest.php
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

namespace Tests\Unit\Services\Internal\Update;


use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Update\TransactionUpdateService;
use Tests\TestCase;
use Log;

/**
 * Class TransactionUpdateServiceTest
 */
class TransactionUpdateServiceTest extends TestCase
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
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     */
    public function testReconcile(): void
    {
        $transaction = $this->user()->transactions()->inRandomOrder()->first();

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->reconcile($transaction->id);
        $this->assertEquals($result->id, $transaction->id);
        $this->assertEquals(true, $result->reconciled);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testReconcileNull(): void
    {
        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->reconcile(-1);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testUpdateBudget(): void
    {

        /** @var Transaction $source */
        $source = $this->user()->transactions()->where('amount', '>', 0)->inRandomOrder()->first();
        $budget = $this->user()->budgets()->inRandomOrder()->first();

        $factory = $this->mock(BudgetFactory::class);
        $factory->shouldReceive('setUser');
        $factory->shouldReceive('find')->andReturn($budget);

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->updateBudget($source, $budget->id);

        $this->assertEquals(1, $result->budgets()->count());
        $this->assertEquals($budget->name, $result->budgets()->first()->name);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testUpdateCategory(): void
    {

        /** @var Transaction $source */
        $source   = $this->user()->transactions()->where('amount', '>', 0)->inRandomOrder()->first();
        $category = $this->user()->categories()->inRandomOrder()->first();

        $factory = $this->mock(CategoryFactory::class);
        $factory->shouldReceive('setUser');
        $factory->shouldReceive('findOrCreate')->andReturn($category);

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->updateCategory($source, $category->name);

        $this->assertEquals(1, $result->categories()->count());
        $this->assertEquals($category->name, $result->categories()->first()->name);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testUpdateDestinationBasic(): void
    {
        /** @var Transaction $source */
        $source = $this->user()->transactions()->where('amount', '>', 0)->inRandomOrder()->first();
        $data   = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => 'Some new description',
            'reconciled'            => false,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'destination_id'        => (int)$source->account_id,
            'destination_name'      => null,
            'category_id'           => null,
            'category_name'         => null,
            'amount'                => $source->amount,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
        ];

        // mock repository:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findNull')->andReturn($source->account);

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->update($source, $data);

        $this->assertEquals($source->id, $result->id);
        $this->assertEquals($result->description, $data['description']);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testUpdateDestinationForeign(): void
    {
        /** @var Transaction $source */
        $source = $this->user()->transactions()->where('amount', '>', 0)->inRandomOrder()->first();
        $data   = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => 'Some new description',
            'reconciled'            => false,
            'foreign_amount'        => '12.34',
            'budget_id'             => null,
            'budget_name'           => null,
            'destination_id'        => (int)$source->account_id,
            'destination_name'      => null,
            'category_id'           => null,
            'category_name'         => null,
            'amount'                => $source->amount,
            'foreign_currency_id'   => 2,
            'foreign_currency_code' => null,
        ];

        // mock repository:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findNull')->andReturn($source->account);

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->update($source, $data);


        $this->assertEquals($source->id, $result->id);
        $this->assertEquals($result->description, $data['description']);
        $this->assertEquals($data['foreign_amount'], $result->foreign_amount);
        $this->assertEquals($data['foreign_currency_id'], $result->foreign_currency_id);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\TransactionUpdateService
     * @covers \FireflyIII\Services\Internal\Support\TransactionServiceTrait
     */
    public function testUpdateSourceBasic(): void
    {
        /** @var Transaction $source */
        $source = $this->user()->transactions()->where('amount', '<', 0)->inRandomOrder()->first();
        $data   = [
            'currency_id'           => 1,
            'currency_code'         => null,
            'description'           => 'Some new description',
            'reconciled'            => false,
            'foreign_amount'        => null,
            'budget_id'             => null,
            'budget_name'           => null,
            'source_id'             => (int)$source->account_id,
            'source_name'           => null,
            'category_id'           => null,
            'category_name'         => null,
            'amount'                => $source->amount,
            'foreign_currency_id'   => null,
            'foreign_currency_code' => null,
        ];

        // mock repository:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findNull')->andReturn($source->account);

        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user());
        $result = $service->update($source, $data);

        $this->assertEquals($source->id, $result->id);
        $this->assertEquals($result->description, $data['description']);


    }
}

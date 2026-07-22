<?php

/*
 * OperationsRepositoryTest.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace Tests\integration\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepository;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepository;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Override;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OperationsRepositoryTest extends TestCase
{
    private TransactionCurrency $eur;
    private OperationsRepository $repository;
    private TransactionCurrency $usd;
    private User $user;

    public function testAnyCurrencyIsConvertedToPrimaryByDefault(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', false);
        $gbp      = TransactionCurrency::query()->where('code', 'GBP')->firstOrFail();
        $expenses = [$this->expense($gbp, pcAmount: '-75')];

        $result = $this->repository->sumCollectedExpenses($expenses, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $this->usd, true);

        $this->assertSame('-75.000000000000', $result[$this->eur->id]['sum']);
    }

    public function testBudgetAggregationIncludesForeignCurrencyByDefault(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', false);
        $budget = Budget::create([
            'user_id'       => $this->user->id,
            'user_group_id' => $this->user->user_group_id,
            'name'          => 'Travel',
            'active'        => true
        ]);
        $expenses = [$this->expense($this->eur, $this->usd, '-100', '-120', budgetId: $budget->id)];

        $result = $this->repository->sumCollectedExpensesByBudget($expenses, $budget);

        $this->assertSame('-120.000000000000', $result[$this->usd->id]['sum']);
    }

    public function testConvertedSqlScopeAcceptsEveryCurrencyByDefault(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', false);
        $this->bindEmptyCollector();

        $result = $this->repository->sumExpenses(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), null, new Collection(), $this->usd, true);

        $this->assertSame([], $result);
    }

    public function testDefaultSqlScopeMatchesPrimaryOrForeignCurrency(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', false);
        $this->bindEmptyCollector('setCurrency');

        $result = $this->repository->sumExpenses(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), null, new Collection(), $this->usd);

        $this->assertSame([], $result);
    }

    public function testForeignCurrencyAmountIsIncludedByDefault(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', false);
        $expenses = [$this->expense($this->eur, $this->usd, amount: '-100', foreignAmount: '-120')];

        $result = $this->repository->sumCollectedExpenses($expenses, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $this->usd);

        $this->assertSame('-120.000000000000', $result[$this->usd->id]['sum']);
    }

    public function testLegacyBehaviorOnlyIncludesMatchingPrimaryCurrency(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', true);
        $gbp             = TransactionCurrency::query()->where('code', 'GBP')->firstOrFail();
        $foreignExpenses = [$this->expense($this->eur, $this->usd, amount: '-100', foreignAmount: '-120')];
        $otherExpenses   = [$this->expense($gbp, pcAmount: '-75')];

        $foreignResult = $this->repository->sumCollectedExpenses($foreignExpenses, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $this->usd);
        $convertedResult = $this->repository->sumCollectedExpenses($otherExpenses, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $this->usd, true);

        $this->assertSame([], $foreignResult);
        $this->assertSame([], $convertedResult);
    }

    public function testLegacySqlScopeMatchesPrimaryCurrencyOnly(): void
    {
        config()->set('firefly.feature_flags.legacy_budget_currency_behavior', true);
        $this->bindEmptyCollector('setNormalCurrency');

        $result = $this->repository->sumExpenses(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), null, new Collection(), $this->usd);

        $this->assertSame([], $result);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user       = $this->createAuthenticatedUser();
        $this->eur        = TransactionCurrency::query()->where('code', 'EUR')->firstOrFail();
        $this->usd        = TransactionCurrency::query()->where('code', 'USD')->firstOrFail();
        $this->repository = new OperationsRepository();
        $this->repository->setUser($this->user);
    }

    private function bindEmptyCollector(null|string $expectedCurrencyMethod = null): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->expects($this->once())->method('setUser')->with($this->user);
        $accountRepository->expects($this->once())->method('getAccountsByType')->willReturn(new Collection());
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);

        $collector = $this->createMock(GroupCollectorInterface::class);
        $collector->method('setUser')->willReturnSelf();
        $collector->method('setRange')->willReturnSelf();
        $collector->method('setTypes')->willReturnSelf();
        $collector->method('getExtractedJournals')->willReturn([]);
        $setCurrencyExpectation       = 'setCurrency' === $expectedCurrencyMethod ? $this->once() : $this->never();
        $setNormalCurrencyExpectation = 'setNormalCurrency' === $expectedCurrencyMethod ? $this->once() : $this->never();
        $collector->expects($setCurrencyExpectation)->method('setCurrency')->with($this->usd)->willReturnSelf();
        $collector->expects($setNormalCurrencyExpectation)->method('setNormalCurrency')->with($this->usd)->willReturnSelf();
        $this->app->instance(GroupCollectorInterface::class, $collector);
    }

    private function expense(
        TransactionCurrency $currency,
        null|TransactionCurrency $foreignCurrency = null,
        string $amount = '-10',
        string $foreignAmount = '0',
        string $pcAmount = '-9',
        int $budgetId = 1
    ): array {
        return [
            'transaction_journal_id'          => 1,
            'budget_id'                       => $budgetId,
            'date'                            => Carbon::parse('2026-01-15'),
            'amount'                          => $amount,
            'pc_amount'                       => $pcAmount,
            'currency_id'                     => $currency->id,
            'currency_name'                   => $currency->name,
            'currency_symbol'                 => $currency->symbol,
            'currency_code'                   => $currency->code,
            'currency_decimal_places'         => $currency->decimal_places,
            'foreign_amount'                  => $foreignAmount,
            'foreign_currency_id'             => $foreignCurrency instanceof TransactionCurrency ? $foreignCurrency->id : 0,
            'foreign_currency_name'           => $foreignCurrency?->name,
            'foreign_currency_symbol'         => $foreignCurrency?->symbol,
            'foreign_currency_code'           => $foreignCurrency?->code,
            'foreign_currency_decimal_places' => $foreignCurrency?->decimal_places
        ];
    }
}

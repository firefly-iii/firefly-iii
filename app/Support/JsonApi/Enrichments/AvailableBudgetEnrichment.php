<?php
/*
 * AvailableBudgetEnrichment.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AvailableBudgetEnrichment implements EnrichmentInterface
{
    private User                                   $user;
    private UserGroup                              $userGroup;
    private TransactionCurrency                    $primaryCurrency;
    private bool                                   $convertToPrimary      = false;
    private array                                  $ids                   = [];
    private Collection                             $collection;
    private array                                  $spentInBudgets        = [];
    private array                                  $spentOutsideBudgets   = [];
    private array                                  $pcSpentInBudgets      = [];
    private array                                  $pcSpentOutsideBudgets = [];
    private readonly NoBudgetRepositoryInterface   $noBudgetRepository;
    private readonly OperationsRepositoryInterface $opsRepository;
    private readonly BudgetRepositoryInterface     $repository;


    private ?Carbon $start = null;
    private ?Carbon $end   = null;

    public function __construct()
    {
        $this->primaryCurrency    = Amount::getPrimaryCurrency();
        $this->convertToPrimary   = Amount::convertToPrimary();
        $this->noBudgetRepository = app(NoBudgetRepositoryInterface::class);
        $this->opsRepository      = app(OperationsRepositoryInterface::class);
        $this->repository         = app(BudgetRepositoryInterface::class);
    }

    #[\Override] public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectSpentInfo();
        $this->appendCollectedData();

        return $this->collection;
    }

    #[\Override] public function enrichSingle(Model | array $model): array | Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    #[\Override] public function setUser(User $user): void
    {
        $this->user = $user;
        $this->setUserGroup($user->userGroup);
    }

    #[\Override] public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
        $this->noBudgetRepository->setUserGroup($userGroup);
        $this->opsRepository->setUserGroup($userGroup);
        $this->repository->setUserGroup($userGroup);
    }

    private function collectIds(): void
    {
        /** @var AvailableBudget $availableBudget */
        foreach ($this->collection as $availableBudget) {
            $this->ids[] = (int) $availableBudget->id;
        }
        $this->ids = array_unique($this->ids);
    }

    private function collectSpentInfo(): void
    {
        $start               = $this->collection->min('start_date');
        $end                 = $this->collection->max('end_date');
        $allActive           = $this->repository->getActiveBudgets();
        $spentInBudgets      = $this->opsRepository->collectExpenses($start, $end, null, $allActive, null);
        $spentOutsideBudgets = $this->noBudgetRepository->collectExpenses($start, $end, null, null, null);
        foreach ($this->collection as $availableBudget) {
            $id                          = (int) $availableBudget->id;
            $filteredSpentInBudgets      = $this->opsRepository->sumCollectedExpenses($spentInBudgets, $availableBudget->start_date, $availableBudget->end_date, $availableBudget->transactionCurrency, false);
            $filteredSpentOutsideBudgets = $this->opsRepository->sumCollectedExpenses($spentOutsideBudgets, $availableBudget->start_date, $availableBudget->end_date, $availableBudget->transactionCurrency, false);
            $this->spentInBudgets[$id]      = array_values($filteredSpentInBudgets);
            $this->spentOutsideBudgets[$id] = array_values($filteredSpentOutsideBudgets);

            if (true === $this->convertToPrimary) {
                $pcFilteredSpentInBudgets         = $this->opsRepository->sumCollectedExpenses($spentInBudgets, $availableBudget->start_date, $availableBudget->end_date, $availableBudget->transactionCurrency, true);
                $pcFilteredSpentOutsideBudgets    = $this->opsRepository->sumCollectedExpenses($spentOutsideBudgets, $availableBudget->start_date, $availableBudget->end_date, $availableBudget->transactionCurrency, true);
                $this->pcSpentInBudgets[$id]      = array_values($pcFilteredSpentInBudgets);
                $this->pcSpentOutsideBudgets[$id] = array_values($pcFilteredSpentOutsideBudgets);
            }


            // filter arrays on date.
            // send them to sumCollection thing.
            // save.
        }

        // first collect, then filter and append.
    }

    private function appendCollectedData(): void
    {
        $spentInsideBudgets    = $this->spentInBudgets;
        $spentOutsideBudgets   = $this->spentOutsideBudgets;
        $pcSpentInBudgets      = $this->pcSpentInBudgets;
        $pcSpentOutsideBudgets = $this->pcSpentOutsideBudgets;
        $this->collection      = $this->collection->map(function (AvailableBudget $item) use ($spentInsideBudgets, $spentOutsideBudgets, $pcSpentInBudgets, $pcSpentOutsideBudgets) {
            $id         = (int) $item->id;
            $meta       = [
                'spent_in_budgets'         => $spentInsideBudgets[$id] ?? [],
                'pc_spent_in_budgets'      => $pcSpentInBudgets[$id] ?? [],

                'spent_outside_budgets'    => $spentOutsideBudgets[$id] ?? [],
                'pc_spent_outside_budgets' => $pcSpentOutsideBudgets[$id] ?? [],
            ];
            $item->meta = $meta;
            return $item;
        });
    }


}

<?php

declare(strict_types=1);

/*
 * PeriodStatisticRepository.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Repositories\PeriodStatistic;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\PeriodStatistic;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Override;

class PeriodStatisticRepository implements PeriodStatisticRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function findPeriodStatistics(Model $model, Carbon $start, Carbon $end, array $types): Collection
    {
        return $model->primaryPeriodStatistics()->where('start', $start)->where('end', $end)->whereIn('type', $types)->get();
    }

    public function findPeriodStatistic(Model $model, Carbon $start, Carbon $end, string $type): Collection
    {
        return $model->primaryPeriodStatistics()->where('start', $start)->where('end', $end)->where('type', $type)->get();
    }

    public function saveStatistic(Model $model, int $currencyId, Carbon $start, Carbon $end, string $type, int $count, string $amount): PeriodStatistic
    {
        $stat                          = new PeriodStatistic();
        $stat->primaryStatable()->associate($model);
        $stat->transaction_currency_id = $currencyId;
        $stat->user_group_id           = $this->getUserGroup()->id;
        $stat->start                   = $start;
        $stat->start_tz                = $start->format('e');
        $stat->end                     = $end;
        $stat->end_tz                  = $end->format('e');
        $stat->amount                  = $amount;
        $stat->count                   = $count;
        $stat->type                    = $type;
        $stat->save();

        Log::debug(sprintf(
            'Saved #%d [currency #%d, Model %s #%d, %s to %s, %d, %s] as new statistic.',
            $stat->id,
            $model::class,
            $model->id,
            $stat->transaction_currency_id,
            $stat->start->toW3cString(),
            $stat->end->toW3cString(),
            $count,
            $amount
        ));

        return $stat;
    }

    public function allInRangeForModel(Model $model, Carbon $start, Carbon $end): Collection
    {
        return $model->primaryPeriodStatistics()->where('start', '>=', $start)->where('end', '<=', $end)->get();
    }

    public function deleteStatisticsForModel(Model $model, Carbon $date): void
    {
        $model->primaryPeriodStatistics()->where('start', '<=', $date)->where('end', '>=', $date)->delete();
    }

    #[Override]
    public function allInRangeForPrefix(string $prefix, Carbon $start, Carbon $end): Collection
    {
        return $this->userGroup
            ->periodStatistics()
            ->where('type', 'LIKE', sprintf('%s%%', $prefix))
            ->where('start', '>=', $start)
            ->where('end', '<=', $end)
            ->get()
        ;
    }

    #[Override]
    public function savePrefixedStatistic(
        string $prefix,
        int $currencyId,
        Carbon $start,
        Carbon $end,
        string $type,
        int $count,
        string $amount
    ): PeriodStatistic {
        $stat                          = new PeriodStatistic();
        $stat->transaction_currency_id = $currencyId;
        $stat->user_group_id           = $this->getUserGroup()->id;
        $stat->start                   = $start;
        $stat->start_tz                = $start->format('e');
        $stat->end                     = $end;
        $stat->end_tz                  = $end->format('e');
        $stat->amount                  = $amount;
        $stat->count                   = $count;
        $stat->type                    = sprintf('%s_%s', $prefix, $type);
        $stat->save();

        Log::debug(sprintf(
            'Saved #%d [currency #%d, type "%s", %s to %s, %d, %s] as new statistic.',
            $stat->id,
            $stat->transaction_currency_id,
            $stat->type,
            $stat->start->toW3cString(),
            $stat->end->toW3cString(),
            $count,
            $amount
        ));

        return $stat;
    }

    #[Override]
    public function deleteStatisticsForPrefix(string $prefix, Collection $dates): void
    {
        $count = $this->userGroup
            ->periodStatistics()
            ->where(function (Builder $q) use ($dates): void {
                foreach ($dates as $date) {
                    $q->where(function (Builder $q1) use ($date): void {
                        $q1->where('start', '<=', $date)->where('end', '>=', $date);
                    });
                }
            })
            ->where('type', 'LIKE', sprintf('%s%%', $prefix))
            ->delete()
        ;
        Log::debug(sprintf('Deleted %d entries for prefix "%s"', $count, $prefix));
    }

    public function deleteStatisticsForType(string $class, Collection $objects, Collection $dates): void
    {
        if (0 === count($objects)) {
            Log::debug(sprintf('Nothing to delete in deleteStatisticsForType("%s")', $class));

            return;
        }
        $count = PeriodStatistic::where('primary_statable_type', $class)
            ->whereIn('primary_statable_id', $objects->pluck('id')->toArray())
            ->where(function (Builder $q) use ($dates): void {
                foreach ($dates as $date) {
                    $q->where(function (Builder $q1) use ($date): void {
                        $q1->where('start', '<=', $date)->where('end', '>=', $date);
                    });
                }
            })
            ->delete()
        ;
        Log::debug(sprintf('Delete %d statistics for %dx %s', $count, $objects->count(), $class));
    }

    #[Override]
    public function deleteStatisticsForCollection(Collection $set): void
    {
        //        Log::debug(sprintf('Delete statistics for %d transaction journals.', count($set)));
        //        // collect all transactions:
        //        $transactions = Transaction::whereIn('transaction_journal_id', $set->pluck('id')->toArray())->get(['transactions.*']);
        //        Log::debug('Collected transaction IDs', $transactions->pluck('id')->toArray());
        //
        //        // collect all accounts and delete stats:
        //        $accounts     = Account::whereIn('id', $transactions->pluck('account_id')->toArray())->get(['accounts.*']);
        //        Log::debug('Collected account IDs', $accounts->pluck('id')->toArray());
        //        $dates        = $set->pluck('date');
        //        $this->deleteStatisticsForType(Account::class, $accounts, $dates);
        //
        //        // remove for no tag, no cat, etc.
        //        if (0 === $categories->count()) {
        //            Log::debug('No categories, delete "no_category" stats.');
        //            $this->deleteStatisticsForPrefix('no_category', $dates);
        //        }
        //        if (0 === $budgets->count()) {
        //            Log::debug('No budgets, delete "no_category" stats.');
        //            $this->deleteStatisticsForPrefix('no_budget', $dates);
        //        }
        //        if (0 === $tags->count()) {
        //            Log::debug('No tags, delete "no_category" stats.');
        //            $this->deleteStatisticsForPrefix('no_tag', $dates);
        //        }
    }
}

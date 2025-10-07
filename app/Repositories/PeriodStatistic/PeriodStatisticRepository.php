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
use FireflyIII\Models\PeriodStatistic;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Override;

class PeriodStatisticRepository implements PeriodStatisticRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function findPeriodStatistics(Model $model, Carbon $start, Carbon $end, array $types): Collection
    {
        return $model->primaryPeriodStatistics()
            ->where('start', $start)
            ->where('end', $end)
            ->whereIn('type', $types)
            ->get()
        ;
    }

    public function findPeriodStatistic(Model $model, Carbon $start, Carbon $end, string $type): Collection
    {
        return $model->primaryPeriodStatistics()
            ->where('start', $start)
            ->where('end', $end)
            ->where('type', $type)
            ->get()
        ;
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
        return $this->userGroup->periodStatistics()
            ->where('type', 'LIKE', sprintf('%s%%', $prefix))
            ->where('start', '>=', $start)->where('end', '<=', $end)->get()
        ;
    }

    #[Override]
    public function savePrefixedStatistic(string $prefix, int $currencyId, Carbon $start, Carbon $end, string $type, int $count, string $amount): PeriodStatistic
    {
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
    public function deleteStatisticsForPrefix(UserGroup $userGroup, string $prefix, Carbon $date): void
    {
        $userGroup->periodStatistics()->where('start', '<=', $date)->where('end', '>=', $date)->where('type', 'LIKE', sprintf('%s%%', $prefix))->delete();
    }
}

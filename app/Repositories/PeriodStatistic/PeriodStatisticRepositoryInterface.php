<?php

declare(strict_types=1);

/*
 * PeriodStatisticRepositoryInterface.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PeriodStatisticRepositoryInterface
{
    public function deleteStatisticsForCollection(Collection $set);

    public function findPeriodStatistics(Model $model, Carbon $start, Carbon $end, array $types): Collection;

    public function findPeriodStatistic(Model $model, Carbon $start, Carbon $end, string $type): Collection;

    public function saveStatistic(Model $model, int $currencyId, Carbon $start, Carbon $end, string $type, int $count, string $amount): PeriodStatistic;

    public function savePrefixedStatistic(
        string $prefix,
        int $currencyId,
        Carbon $start,
        Carbon $end,
        string $type,
        int $count,
        string $amount
    ): PeriodStatistic;

    public function allInRangeForModel(Model $model, Carbon $start, Carbon $end): Collection;

    public function allInRangeForPrefix(string $prefix, Carbon $start, Carbon $end): Collection;

    public function deleteStatisticsForModel(Model $model, Carbon $date): void;

    public function deleteStatisticsForPrefix(string $prefix, Collection $dates): void;
}

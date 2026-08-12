<?php




declare(strict_types=1);



namespace FireflyIII\Repositories\PeriodStatistic;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\Models\PeriodStatistic;
use FireflyIII\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PeriodStatisticRepositoryInterface
{
    public function allInRangeForModel(Account|Category|Tag $model, Carbon $start, Carbon $end): Collection;

    public function allInRangeForPrefix(string $prefix, Carbon $start, Carbon $end): Collection;

    public function deleteStatisticsForCollection(Collection $set): void;

    public function deleteStatisticsForModel(Model $model, Carbon $date): void;

    public function deleteStatisticsForPrefix(string $prefix, Collection $dates): void;

    public function deleteStatisticsForType(string $class, Collection $objects, Collection $dates): void;

    public function findPeriodStatistic(Model $model, Carbon $start, Carbon $end, string $type): Collection;

    public function findPeriodStatistics(Model $model, Carbon $start, Carbon $end, array $types): Collection;

    public function savePrefixedStatistic(
        string $prefix,
        int $currencyId,
        Carbon $start,
        Carbon $end,
        string $type,
        int $count,
        string $amount
    ): PeriodStatistic;

    public function saveStatistic(Model $model, int $currencyId, Carbon $start, Carbon $end, string $type, int $count, string $amount): PeriodStatistic;
}

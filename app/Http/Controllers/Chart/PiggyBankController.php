<?php
/**
 * PiggyBankController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class PiggyBankController.
 */
class PiggyBankController extends Controller
{
    use DateCalculation;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * PiggyBankController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * Shows the piggy bank history.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return JsonResponse
     *
     */
    public function history(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.piggy-bank.history');
        $cache->addProperty($piggyBank->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $set = $repository->getEvents($piggyBank);
        $set = $set->reverse();

        // get first event or start date of piggy bank or today
        $startDate = $piggyBank->start_date ?? new Carbon;

        /** @var PiggyBankEvent $first */
        $firstEvent = $set->first();
        $firstDate  = null === $firstEvent ? new Carbon : $firstEvent->date;

        // which ever is older:
        $oldest = $startDate->lt($firstDate) ? $startDate : $firstDate;
        $today  = new Carbon;
        // depending on diff, do something with range of chart.
        $step = $this->calculateStep($oldest, $today);

        $chartData = [];
        while ($oldest <= $today) {
            /** @var Collection $filtered */
            $filtered          = $set->filter(
                function (PiggyBankEvent $event) use ($oldest) {
                    return $event->date->lte($oldest);
                }
            );
            $currentSum        = $filtered->sum('amount');
            $label             = $oldest->formatLocalized((string)trans('config.month_and_day'));
            $chartData[$label] = $currentSum;
            $oldest            = app('navigation')->addPeriod($oldest, $step, 0);
        }
        /** @var Collection $finalFiltered */
        $finalFiltered          = $set->filter(
            function (PiggyBankEvent $event) use ($today) {
                return $event->date->lte($today);
            }
        );
        $finalSum               = $finalFiltered->sum('amount');
        $finalLabel             = $today->formatLocalized((string)trans('config.month_and_day'));
        $chartData[$finalLabel] = $finalSum;

        $data = $this->generator->singleSet($piggyBank->name, $chartData);
        $cache->store($data);

        return response()->json($data);
    }
}

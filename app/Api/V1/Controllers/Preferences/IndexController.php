<?php
declare(strict_types=1);
/*
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Preferences;


use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Facades\Navigation;
use Illuminate\Http\JsonResponse;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    public const DATE_FORMAT = 'Y-m-d';

    /**
     * Return users preferred date range settings, the current period
     * and some previous / next periods.
     *
     * @return JsonResponse
     */
    public function dateRanges(): JsonResponse
    {
        $range    = app('preferences')->get('viewRange', '1M')->data;
        $return   = [
            'range'   => $range,
            'ranges'  => [],
            'default' => null,
        ];
        $today    = Carbon::today(config('app.timezone'));
        $start    = Navigation::startOfPeriod($today, $range);
        $todayStr = $today->format(self::DATE_FORMAT);
        // optional date ranges. Maybe to be configured later
        // current $period
        $title                    = (string)Navigation::periodShow($start, $range);
        $return['default']        = $title;
        $return['ranges'][$title] = [$start->format(self::DATE_FORMAT),
                                     Navigation::endOfPeriod($start, $range)->format(self::DATE_FORMAT)];
        // previous $period
        $previousStart            = Navigation::subtractPeriod($start, $range);
        $title                    = (string)Navigation::periodShow($previousStart, $range);
        $return['ranges'][$title] = [$previousStart->format(self::DATE_FORMAT),
                                     Navigation::endOfPeriod($previousStart, $range)->format(self::DATE_FORMAT)];
        // next $period
        $nextStart                = Navigation::addPeriod($start, $range, 0);
        $title                    = (string)Navigation::periodShow($nextStart, $range);
        $return['ranges'][$title] = [$nextStart->format(self::DATE_FORMAT),
                                     Navigation::endOfPeriod($nextStart, $range)->format(self::DATE_FORMAT)];
        // --
        // last seven days:
        $seven                    = Carbon::today()->subDays(7);
        $title                    = (string)trans('firefly.last_seven_days');
        $return['ranges'][$title] = [$seven->format(self::DATE_FORMAT), $todayStr];
        // last 30 days:
        $thirty                   = Carbon::today()->subDays(30);
        $title                    = (string)trans('firefly.last_thirty_days');
        $return['ranges'][$title] = [$thirty->format(self::DATE_FORMAT), $todayStr];
        // last 180 days
        $long                     = Carbon::today()->subDays(180);
        $title                    = (string)trans('firefly.last_180_days');
        $return['ranges'][$title] = [$long->format(self::DATE_FORMAT), $todayStr];
        // YTD
        $YTD                      = Carbon::today()->startOfYear();
        $title                    = (string)trans('firefly.YTD');
        $return['ranges'][$title] = [$YTD->format(self::DATE_FORMAT), $todayStr];
        // ---
        // everything
        $repository               = app(JournalRepositoryInterface::class);
        $journal                  = $repository->firstNull();
        $first                    = null === $journal ? clone $YTD : clone $journal->date;
        $title                    = (string)trans('firefly.everything');
        $return['ranges'][$title] = [$first->format(self::DATE_FORMAT), $todayStr];

        return response()->json($return);
    }

}

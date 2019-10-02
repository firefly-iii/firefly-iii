<?php
/**
 * Date.php
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

namespace FireflyIII\Support\Binder;

use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use Illuminate\Routing\Route;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Date.
 */
class Date implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return Carbon
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Carbon
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app(FiscalHelperInterface::class);

        $magicWords = [
            'currentMonthStart' => Carbon::now()->startOfMonth(),
            'currentMonthEnd'   => Carbon::now()->endOfMonth(),
            'currentYearStart'  => Carbon::now()->startOfYear(),
            'currentYearEnd'    => Carbon::now()->endOfYear(),

            'previousMonthStart' => Carbon::now()->startOfMonth()->subDay()->startOfMonth(),
            'previousMonthEnd'   => Carbon::now()->startOfMonth()->subDay()->endOfMonth(),
            'previousYearStart'  => Carbon::now()->startOfYear()->subDay()->startOfYear(),
            'previousYearEnd'    => Carbon::now()->startOfYear()->subDay()->endOfYear(),

            'currentFiscalYearStart'  => $fiscalHelper->startOfFiscalYear(Carbon::now()),
            'currentFiscalYearEnd'    => $fiscalHelper->endOfFiscalYear(Carbon::now()),
            'previousFiscalYearStart' => $fiscalHelper->startOfFiscalYear(Carbon::now())->subYear(),
            'previousFiscalYearEnd'   => $fiscalHelper->endOfFiscalYear(Carbon::now())->subYear(),
        ];
        if (isset($magicWords[$value])) {
            $return = $magicWords[$value];
            Log::debug(sprintf('User requests "%s", so will return "%s"', $value, $return));

            return $return;
        }

        try {
            $result = new Carbon($value);
        } catch (Exception $e) {
            Log::error(sprintf('Could not parse date "%s" for user #%d: %s', $value, auth()->user()->id, $e->getMessage()));
            throw new NotFoundHttpException;
        }

        return $result;
    }
}

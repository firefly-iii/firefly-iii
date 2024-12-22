<?php

/**
 * Date.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Date.
 */
class Date implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Carbon
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app(FiscalHelperInterface::class);

        $magicWords = [
            'currentMonthStart' => today(config('app.timezone'))->startOfMonth(),
            'currentMonthEnd'   => today(config('app.timezone'))->endOfMonth(),
            'currentYearStart'  => today(config('app.timezone'))->startOfYear(),
            'currentYearEnd'    => today(config('app.timezone'))->endOfYear(),

            'previousMonthStart' => today(config('app.timezone'))->startOfMonth()->subDay()->startOfMonth(),
            'previousMonthEnd'   => today(config('app.timezone'))->startOfMonth()->subDay()->endOfMonth(),
            'previousYearStart'  => today(config('app.timezone'))->startOfYear()->subDay()->startOfYear(),
            'previousYearEnd'    => today(config('app.timezone'))->startOfYear()->subDay()->endOfYear(),

            'currentFiscalYearStart'  => $fiscalHelper->startOfFiscalYear(today(config('app.timezone'))),
            'currentFiscalYearEnd'    => $fiscalHelper->endOfFiscalYear(today(config('app.timezone'))),
            'previousFiscalYearStart' => $fiscalHelper->startOfFiscalYear(today(config('app.timezone')))->subYear(),
            'previousFiscalYearEnd'   => $fiscalHelper->endOfFiscalYear(today(config('app.timezone')))->subYear(),
        ];
        if (array_key_exists($value, $magicWords)) {
            $return = $magicWords[$value];
            app('log')->debug(sprintf('User requests "%s", so will return "%s"', $value, $return));

            return $return;
        }

        try {
            $result = new Carbon($value);
        } catch (InvalidDateException | InvalidFormatException $e) { // @phpstan-ignore-line
            $message = sprintf('Could not parse date "%s" for user #%d: %s', $value, auth()->user()->id, $e->getMessage());
            app('log')->error($message);

            throw new NotFoundHttpException('Could not parse value', $e);
        }

        return $result;
    }
}

<?php

declare(strict_types=1);
/*
 * ExchangeRateConverter.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Http\Api;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;

/**
 * Class ExchangeRateConverter
 */
class ExchangeRateConverter
{
    use ConvertsExchangeRates;

    /**
     * @param TransactionCurrency $from
     * @param TransactionCurrency $to
     * @param Carbon              $date
     *
     * @return string
     * @throws FireflyException
     */
    public function getCurrencyRate(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        if (null === $this->enabled) {
            $this->getPreference();
        }

        // if not enabled, return "1"
        if (false === $this->enabled) {
            return '1';
        }

        $rate = $this->getRate($from, $to, $date);
        return '0' === $rate ? '1' : $rate;
    }


}

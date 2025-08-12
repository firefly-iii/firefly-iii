<?php

/*
 * AvailableBudgetRepository.php
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

declare(strict_types=1);

namespace FireflyIII\Repositories\UserGroups\Budget;

use Carbon\Carbon;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Facades\Log;

/**
 * Class AvailableBudgetRepository
 *
 * @deprecated
 */
class AvailableBudgetRepository implements AvailableBudgetRepositoryInterface
{
    use UserGroupTrait;

    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $return           = [];
        $converter        = new ExchangeRateConverter();
        $primary          = app('amount')->getPrimaryCurrency();
        $availableBudgets = $this->userGroup->availableBudgets()
            ->where('start_date', $start->format('Y-m-d'))
            ->where('end_date', $end->format('Y-m-d'))->get()
        ;

        /** @var AvailableBudget $availableBudget */
        foreach ($availableBudgets as $availableBudget) {
            $currencyId                       = $availableBudget->transaction_currency_id;
            $return[$currencyId] ??= [
                'currency_id'                     => $currencyId,
                'currency_code'                   => $availableBudget->transactionCurrency->code,
                'currency_symbol'                 => $availableBudget->transactionCurrency->symbol,
                'currency_name'                   => $availableBudget->transactionCurrency->name,
                'currency_decimal_places'         => $availableBudget->transactionCurrency->decimal_places,
                'primary_currency_id'             => $primary->id,
                'primary_currency_code'           => $primary->code,
                'primary_currency_symbol'         => $primary->symbol,
                'primary_currency_name'           => $primary->name,
                'primary_currency_decimal_places' => $primary->decimal_places,
                'amount'                          => '0',
                'pc_amount'                       => '0',
            ];
            $pcAmount                         = $converter->convert($availableBudget->transactionCurrency, $primary, $availableBudget->start_date, $availableBudget->amount);
            $return[$currencyId]['amount']    = bcadd($return[$currencyId]['amount'], (string) $availableBudget->amount);
            $return[$currencyId]['pc_amount'] = bcadd($return[$currencyId]['pc_amount'], $pcAmount);
        }
        $converter->summarize();

        return $return;
    }
}

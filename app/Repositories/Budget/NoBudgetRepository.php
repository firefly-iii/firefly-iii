<?php

/**
 * NoBudgetRepository.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class NoBudgetRepository
 */
class NoBudgetRepository implements NoBudgetRepositoryInterface
{
    /** @var User */
    private $user;

    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = app('navigation')->preferredCarbonFormat($start, $end);

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $collector->withoutBudget();
        $journals     = $collector->getExtractedJournals();
        $data         = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId                          = (int) $journal['currency_id'];

            $data[$currencyId] ??= [
                'id'                      => 0,
                'name'                    => sprintf('%s (%s)', trans('firefly.no_budget'), $journal['currency_name']),
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_code'           => $journal['currency_code'],
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
                'entries'                 => [],
            ];
            $date                                = $journal['date']->format($carbonFormat);

            if (!array_key_exists($date, $data[$currencyId]['entries'])) {
                $data[$currencyId]['entries'][$date] = '0';
            }
            $data[$currencyId]['entries'][$date] = bcadd($data[$currencyId]['entries'][$date], $journal['amount']);
        }

        return $data;
    }

    /**
     * @deprecated
     */
    public function spentInPeriodWoBudgetMc(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals   = $collector->getExtractedJournals();
        $return     = [];
        $total      = [];
        $currencies = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $code         = $journal['currency_code'];
            if (!array_key_exists($code, $currencies)) {
                $currencies[$code] = [
                    'id'             => $journal['currency_id'],
                    'name'           => $journal['currency_name'],
                    'symbol'         => $journal['currency_symbol'],
                    'decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $total[$code] = array_key_exists($code, $total) ? bcadd($total[$code], $journal['amount']) : $journal['amount'];
        }
        foreach ($total as $code => $spent) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => (string) $currency['id'],
                'currency_code'           => $code,
                'currency_name'           => $currency['name'],
                'currency_symbol'         => $currency['symbol'],
                'currency_decimal_places' => $currency['decimal_places'],
                'amount'                  => app('steam')->bcround($spent, $currency['decimal_places']),
            ];
        }

        return $return;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * TODO this method does not include multi currency. It just counts.
     * TODO this probably also applies to the other "sumExpenses" methods.
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?TransactionCurrency $currency = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $currency) {
            $collector->setCurrency($currency);
        }
        $collector->withoutBudget();
        $collector->withBudgetInformation();
        $journals  = $collector->getExtractedJournals();
        $array     = [];

        foreach ($journals as $journal) {
            $currencyId                = (int) $journal['currency_id'];
            $array[$currencyId] ??= [
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
            ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($journal['amount']));
        }

        return $array;
    }
}

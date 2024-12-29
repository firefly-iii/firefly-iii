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
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

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
        $convertToNative = Amount::convertToNative($this->user);
        $default         = Amount::getDefaultCurrency();

        foreach ($journals as $journal) {
            // same as in the other methods.
            $amount                    = '0';
            $currencyId                = (int) $journal['currency_id'];
            $currencyName              = $journal['currency_name'];
            $currencySymbol            = $journal['currency_symbol'];
            $currencyCode              = $journal['currency_code'];
            $currencyDecimalPlaces     = $journal['currency_decimal_places'];

            if ($convertToNative) {
                $useNative = $default->id !== (int) $journal['currency_id'];
                $amount    = Amount::getAmountFromJournal($journal);
                if ($useNative) {
                    Log::debug(sprintf('Journal #%d switches to native amount (original is %s)', $journal['transaction_journal_id'], $journal['currency_code']));
                    $currencyId            = $default->id;
                    $currencyName          = $default->name;
                    $currencySymbol        = $default->symbol;
                    $currencyCode          = $default->code;
                    $currencyDecimalPlaces = $default->decimal_places;
                }
            }
            if (!$convertToNative) {
                $amount = $journal['amount'];
                // if the amount is not in $currency (but should be), use the foreign_amount if that one is correct.
                // otherwise, ignore the transaction all together.
                if (null !== $currency && $currencyId !== $currency->id && $currency->id === (int) $journal['foreign_currency_id']) {
                    Log::debug(sprintf('Journal #%d switches to foreign amount because it matches native.', $journal['transaction_journal_id']));
                    $amount                = $journal['foreign_amount'];
                    $currencyId            = (int) $journal['foreign_currency_id'];
                    $currencyName          = $journal['foreign_currency_name'];
                    $currencySymbol        = $journal['foreign_currency_symbol'];
                    $currencyCode          = $journal['foreign_currency_code'];
                    $currencyDecimalPlaces = $journal['foreign_currency_decimal_places'];
                }
            }

            $array[$currencyId] ??= [
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_name'           => $currencyName,
                'currency_symbol'         => $currencySymbol,
                'currency_code'           => $currencyCode,
                'currency_decimal_places' => $currencyDecimalPlaces,
            ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($amount));
        }

        return $array;
    }
}

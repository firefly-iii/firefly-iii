<?php

/**
 * DoubleController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class DoubleController
 */
class DoubleController extends Controller
{
    use AugumentData;

    protected AccountRepositoryInterface  $accountRepository;
    private OperationsRepositoryInterface $opsRepository;

    /**
     * Constructor for ExpenseController
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->opsRepository     = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function avgExpenses(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listExpenses($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $sourceId                  = $journal['source_account_id'];
                $key                       = sprintf('%d-%d', $sourceId, $currency['currency_id']);
                $result[$key] ??= [
                    'transactions'            => 0,
                    'sum'                     => '0',
                    'avg'                     => '0',
                    'avg_float'               => 0,
                    'source_account_name'     => $journal['source_account_name'],
                    'source_account_id'       => $journal['source_account_id'],
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];
                ++$result[$key]['transactions'];
                $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string) $result[$key]['transactions']);
                $result[$key]['avg_float'] = (float) $result[$key]['avg'];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts  = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.double.partials.avg-expenses', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $result;
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function avgIncome(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listIncome($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $destinationId             = $journal['destination_account_id'];
                $key                       = sprintf('%d-%d', $destinationId, $currency['currency_id']);
                $result[$key] ??= [
                    'transactions'             => 0,
                    'sum'                      => '0',
                    'avg'                      => '0',
                    'avg_float'                => 0,
                    'destination_account_name' => $journal['destination_account_name'],
                    'destination_account_id'   => $journal['destination_account_id'],
                    'currency_id'              => $currency['currency_id'],
                    'currency_name'            => $currency['currency_name'],
                    'currency_symbol'          => $currency['currency_symbol'],
                    'currency_decimal_places'  => $currency['currency_decimal_places'],
                ];
                ++$result[$key]['transactions'];
                $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string) $result[$key]['transactions']);
                $result[$key]['avg_float'] = (float) $result[$key]['avg'];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts  = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.double.partials.avg-income', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $result;
    }

    /**
     * @return Factory|View
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function operations(Collection $accounts, Collection $double, Carbon $start, Carbon $end)
    {
        $withCounterpart = $this->accountRepository->expandWithDoubles($double);
        $together        = $accounts->merge($withCounterpart);
        $report          = [];
        $sums            = [];
        // see what happens when we collect transactions.
        $spent           = $this->opsRepository->listExpenses($start, $end, $together);
        $earned          = $this->opsRepository->listIncome($start, $end, $together);
        // group and list per account name (as long as its not in accounts, only in double)

        /** @var array $currency */
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] ??= [
                'spent'                   => '0',
                'earned'                  => '0',
                'sum'                     => '0',
                'currency_id'             => $currency['currency_id'],
                'currency_name'           => $currency['currency_name'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
            ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $destId                           = $journal['destination_account_id'];
                $destName                         = $journal['destination_account_name'];
                $destIban                         = $journal['destination_account_iban'];
                $genericName                      = $this->getCounterpartName($withCounterpart, $destId, $destName, $destIban);
                $objectName                       = sprintf('%s (%s)', $genericName, $currency['currency_name']);
                $report[$objectName] ??= [
                    'dest_name'               => '',
                    'dest_iban'               => '',
                    'source_name'             => '',
                    'source_iban'             => '',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                ];
                // set name
                $report[$objectName]['dest_name'] = $destName;
                $report[$objectName]['dest_iban'] = $destIban;

                // add amounts:
                $report[$objectName]['spent']     = bcadd($report[$objectName]['spent'], $journal['amount']);
                $report[$objectName]['sum']       = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['spent']       = bcadd($sums[$currencyId]['spent'], $journal['amount']);
                $sums[$currencyId]['sum']         = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        /** @var array $currency */
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] ??= [
                'spent'                   => '0',
                'earned'                  => '0',
                'sum'                     => '0',
                'currency_id'             => $currency['currency_id'],
                'currency_name'           => $currency['currency_name'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
            ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $sourceId                           = $journal['source_account_id'];
                $sourceName                         = $journal['source_account_name'];
                $sourceIban                         = $journal['source_account_iban'];
                $genericName                        = $this->getCounterpartName($withCounterpart, $sourceId, $sourceName, $sourceIban);
                $objectName                         = sprintf('%s (%s)', $genericName, $currency['currency_name']);
                $report[$objectName] ??= [
                    'dest_name'               => '',
                    'dest_iban'               => '',
                    'source_name'             => '',
                    'source_iban'             => '',
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                ];

                // set name
                $report[$objectName]['source_name'] = $sourceName;
                $report[$objectName]['source_iban'] = $sourceIban;

                // add amounts:
                $report[$objectName]['earned']      = bcadd($report[$objectName]['earned'], $journal['amount']);
                $report[$objectName]['sum']         = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['earned']        = bcadd($sums[$currencyId]['earned'], $journal['amount']);
                $sums[$currencyId]['sum']           = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        return view('reports.double.partials.accounts', compact('sums', 'report'));
    }

    /**
     * TODO this method is duplicated.
     */
    private function getCounterpartName(Collection $accounts, int $id, string $name, ?string $iban): string
    {
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name && $account->id !== $id) {
                return $account->name;
            }
            if (null !== $account->iban && $account->iban === $iban && $account->id !== $id) {
                return $account->iban;
            }
        }

        return $name;
    }

    /**
     * @return Factory|View
     */
    public function operationsPerAsset(Collection $accounts, Collection $double, Carbon $start, Carbon $end)
    {
        $withCounterpart = $this->accountRepository->expandWithDoubles($double);
        $together        = $accounts->merge($withCounterpart);
        $report          = [];
        $sums            = [];
        // see what happens when we collect transactions.
        $spent           = $this->opsRepository->listExpenses($start, $end, $together);
        $earned          = $this->opsRepository->listIncome($start, $end, $together);
        // group and list per account name (as long as its not in accounts, only in double)

        /** @var array $currency */
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] ??= [
                'spent'                   => '0',
                'earned'                  => '0',
                'sum'                     => '0',
                'currency_id'             => $currency['currency_id'],
                'currency_name'           => $currency['currency_name'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
            ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $objectName                   = sprintf('%s (%s)', $journal['source_account_name'], $currency['currency_name']);
                $report[$objectName] ??= [
                    'account_id'              => $journal['source_account_id'],
                    'account_name'            => $objectName,
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                ];
                // set name
                // add amounts:
                $report[$objectName]['spent'] = bcadd($report[$objectName]['spent'], $journal['amount']);
                $report[$objectName]['sum']   = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['spent']   = bcadd($sums[$currencyId]['spent'], $journal['amount']);
                $sums[$currencyId]['sum']     = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        /** @var array $currency */
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            $sums[$currencyId] ??= [
                'spent'                   => '0',
                'earned'                  => '0',
                'sum'                     => '0',
                'currency_id'             => $currency['currency_id'],
                'currency_name'           => $currency['currency_name'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
            ];

            /** @var array $journal */
            foreach ($currency['transaction_journals'] as $journal) {
                $objectName                    = sprintf('%s (%s)', $journal['destination_account_name'], $currency['currency_name']);
                $report[$objectName] ??= [
                    'account_id'              => $journal['destination_account_id'],
                    'account_name'            => $objectName,
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent'                   => '0',
                    'earned'                  => '0',
                    'sum'                     => '0',
                ];

                // add amounts:
                $report[$objectName]['earned'] = bcadd($report[$objectName]['earned'], $journal['amount']);
                $report[$objectName]['sum']    = bcadd($report[$objectName]['sum'], $journal['amount']);
                $sums[$currencyId]['earned']   = bcadd($sums[$currencyId]['earned'], $journal['amount']);
                $sums[$currencyId]['sum']      = bcadd($sums[$currencyId]['sum'], $journal['amount']);
            }
        }

        return view('reports.double.partials.accounts-per-asset', compact('sums', 'report'));
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function topExpenses(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listExpenses($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $result[] = [
                    'description'              => $journal['description'],
                    'transaction_group_id'     => $journal['transaction_group_id'],
                    'amount_float'             => (float) $journal['amount'],
                    'amount'                   => $journal['amount'],
                    'date'                     => $journal['date']->isoFormat($this->monthAndDayFormat),
                    'date_sort'                => $journal['date']->format('Y-m-d'),
                    'destination_account_name' => $journal['destination_account_name'],
                    'destination_account_id'   => $journal['destination_account_id'],
                    'currency_id'              => $currency['currency_id'],
                    'currency_name'            => $currency['currency_name'],
                    'currency_symbol'          => $currency['currency_symbol'],
                    'currency_decimal_places'  => $currency['currency_decimal_places'],
                    'source_account_name'      => $journal['source_account_name'],
                    'source_account_id'        => $journal['source_account_id'],
                ];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts  = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.double.partials.top-expenses', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $result;
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function topIncome(Collection $accounts, Collection $doubles, Carbon $start, Carbon $end)
    {
        $expanded = $this->accountRepository->expandWithDoubles($doubles);
        $accounts = $accounts->merge($expanded);
        $spent    = $this->opsRepository->listIncome($start, $end, $accounts);
        $result   = [];
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $result[] = [
                    'description'              => $journal['description'],
                    'transaction_group_id'     => $journal['transaction_group_id'],
                    'amount_float'             => (float) $journal['amount'],
                    'amount'                   => $journal['amount'],
                    'date'                     => $journal['date']->isoFormat($this->monthAndDayFormat),
                    'date_sort'                => $journal['date']->format('Y-m-d'),
                    'destination_account_name' => $journal['destination_account_name'],
                    'destination_account_id'   => $journal['destination_account_id'],
                    'currency_id'              => $currency['currency_id'],
                    'currency_name'            => $currency['currency_name'],
                    'currency_symbol'          => $currency['currency_symbol'],
                    'currency_decimal_places'  => $currency['currency_decimal_places'],
                    'source_account_name'      => $journal['source_account_name'],
                    'source_account_id'        => $journal['source_account_id'],
                ];
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts  = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.double.partials.top-income', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($e->getMessage(), 0, $e);
        }

        return $result;
    }
}

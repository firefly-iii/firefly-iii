<?php

/*
 * CategoryController.php
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

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRangeRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class BudgetController
 */
class CategoryController extends Controller
{
    use CleansChartData;
    use ValidatesUserGroupTrait;

    protected array $acceptedRoles = [UserRoleEnum::READ_ONLY];

    private AccountRepositoryInterface  $accountRepos;
    private CurrencyRepositoryInterface $currencyRepos;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->accountRepos  = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);
                $this->accountRepos->setUserGroup($this->userGroup);
                $this->currencyRepos->setUserGroup($this->userGroup);
                $this->accountRepos->setUser($this->user);
                $this->currencyRepos->setUser($this->user);

                return $next($request);
            }
        );
    }

    /**
     * TODO may be worth to move to a handler but the data is simple enough.
     * TODO see autoComplete/account controller
     *
     * @throws FireflyException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function overview(DateRangeRequest $request): JsonResponse
    {
        /** @var Carbon $start */
        $start      = $request->attributes->get('start');

        /** @var Carbon $end */
        $end        = $request->attributes->get('end');
        $accounts   = $this->accountRepos->getAccountsByType([AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::ASSET->value]);
        $currencies = [];
        $return     = [];
        $converter  = new ExchangeRateConverter();

        // get journals for entire period:
        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts)->withCategoryInformation();
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value]);
        $journals   = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($journals as $journal) {
            // find journal:
            $journalCurrencyId              = (int)$journal['currency_id'];
            $type                           = $journal['transaction_type_type'];
            $currency                       = $currencies[$journalCurrencyId] ?? $this->currencyRepos->find($journalCurrencyId);
            $currencies[$journalCurrencyId] = $currency;
            $currencyId                     = $currency->id;
            $currencyName                   = $currency->name;
            $currencyCode                   = $currency->code;
            $currencySymbol                 = $currency->symbol;
            $currencyDecimalPlaces          = $currency->decimal_places;
            $amount                         = Steam::positive((string)$journal['amount']);
            $pcAmount                       = null;

            // overrule if necessary:
            if ($this->convertToPrimary && $journalCurrencyId === $this->primaryCurrency->id) {
                $pcAmount = $amount;
            }
            if ($this->convertToPrimary && $journalCurrencyId !== $this->primaryCurrency->id) {
                $currencyId            = $this->primaryCurrency->id;
                $currencyName          = $this->primaryCurrency->name;
                $currencyCode          = $this->primaryCurrency->code;
                $currencySymbol        = $this->primaryCurrency->symbol;
                $currencyDecimalPlaces = $this->primaryCurrency->decimal_places;
                $pcAmount              = $converter->convert($currency, $this->primaryCurrency, $journal['date'], $amount);
                Log::debug(sprintf('Converted %s %s to %s %s', $journal['currency_code'], $amount, $this->primaryCurrency->code, $pcAmount));
            }


            $categoryName                   = $journal['category_name'] ?? (string)trans('firefly.no_category');
            $key                            = sprintf('%s-%s', $categoryName, $currencyCode);
            // create arrays
            $return[$key] ??= [
                'label'                           => $categoryName,
                'currency_id'                     => (string)$currencyId,
                'currency_name'                   => $currencyName,
                'currency_code'                   => $currencyCode,
                'currency_symbol'                 => $currencySymbol,
                'currency_decimal_places'         => $currencyDecimalPlaces,
                'primary_currency_id'             => (string)$this->primaryCurrency->id,
                'primary_currency_name'           => $this->primaryCurrency->name,
                'primary_currency_code'           => $this->primaryCurrency->code,
                'primary_currency_symbol'         => $this->primaryCurrency->symbol,
                'primary_currency_decimal_places' => $this->primaryCurrency->decimal_places,
                'period'                          => null,
                'start_date'                      => $start->toAtomString(),
                'end_date'                        => $end->toAtomString(),
                'yAxisID'                         => 0,
                'type'                            => 'bar',
                'entries'                         => [
                    'spent'  => '0',
                    'earned' => '0',
                ],
                'pc_entries'                      => [
                    'spent'  => '0',
                    'earned' => '0',
                ],
            ];

            // add monies
            // expenses to spent
            if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
                $return[$key]['entries']['spent'] = bcadd($return[$key]['entries']['spent'], $amount);
                if (null !== $pcAmount) {
                    $return[$key]['pc_entries']['spent'] = bcadd($return[$key]['pc_entries']['spent'], $pcAmount);
                }

                continue;
            }
            // positive amount = earned
            if (TransactionTypeEnum::DEPOSIT->value === $type) {
                $return[$key]['entries']['earned'] = bcadd($return[$key]['entries']['earned'], $amount);
                if (null !== $pcAmount) {
                    $return[$key]['pc_entries']['earned'] = bcadd($return[$key]['pc_entries']['earned'], $pcAmount);
                }
            }
        }
        $return     = array_values($return);

        // order by amount
        usort($return, static fn (array $a, array $b) => ((float)$a['entries']['spent'] + (float)$a['entries']['earned']) < ((float)$b['entries']['spent'] + (float)$b['entries']['earned']) ? 1 : -1);

        return response()->json($this->clean($return));
    }
}

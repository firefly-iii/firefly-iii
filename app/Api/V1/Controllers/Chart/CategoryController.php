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
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
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
                $this->accountRepos  = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);
                $userGroup           = $this->validateUserGroup($request);
                $this->accountRepos->setUserGroup($userGroup);
                $this->currencyRepos->setUserGroup($userGroup);

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
    public function dashboard(DateRequest $request): JsonResponse
    {
        /** @var Carbon $start */
        $start      = $this->parameters->get('start');

        /** @var Carbon $end */
        $end        = $this->parameters->get('end');
        $accounts   = $this->accountRepos->getAccountsByType([AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::ASSET->value]);
        $currencies = [];
        $return     = [];
        $converter  = new ExchangeRateConverter();

        // get journals for entire period:
        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts)->withCategoryInformation();
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::RECONCILIATION->value]);
        $journals   = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($journals as $journal) {
            // find journal:
            $journalCurrencyId              = (int)$journal['currency_id'];
            $currency                       = $currencies[$journalCurrencyId] ?? $this->currencyRepos->find($journalCurrencyId);
            $currencies[$journalCurrencyId] = $currency;
            $currencyId                     = (int)$currency->id;
            $currencyName                   = (string)$currency->name;
            $currencyCode                   = (string)$currency->code;
            $currencySymbol                 = (string)$currency->symbol;
            $currencyDecimalPlaces          = (int)$currency->decimal_places;
            $amount                         = app('steam')->positive($journal['amount']);

            // overrule if necessary:
            if ($this->convertToPrimary && $journalCurrencyId !== $this->primaryCurrency->id) {
                $currencyId            = (int)$this->primaryCurrency->id;
                $currencyName          = (string)$this->primaryCurrency->name;
                $currencyCode          = (string)$this->primaryCurrency->code;
                $currencySymbol        = (string)$this->primaryCurrency->symbol;
                $currencyDecimalPlaces = (int)$this->primaryCurrency->decimal_places;
                $convertedAmount       = $converter->convert($currency, $this->primaryCurrency, $journal['date'], $amount);
                Log::debug(sprintf('Converted %s %s to %s %s', $journal['currency_code'], $amount, $this->primaryCurrency->code, $convertedAmount));
                $amount                = $convertedAmount;
            }


            $categoryName                   = $journal['category_name'] ?? (string)trans('firefly.no_category');
            $key                            = sprintf('%s-%s', $categoryName, $currencyCode);
            // create arrays
            $return[$key] ??= [
                'label'                   => $categoryName,
                'currency_id'             => (string)$currencyId,
                'currency_code'           => $currencyCode,
                'currency_name'           => $currencyName,
                'currency_symbol'         => $currencySymbol,
                'currency_decimal_places' => $currencyDecimalPlaces,
                'period'                  => null,
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'amount'                  => '0',
            ];

            // add monies
            $return[$key]['amount']         = bcadd($return[$key]['amount'], (string)$amount);
        }
        $return     = array_values($return);

        // order by amount
        usort($return, static fn (array $a, array $b) => (float)$a['amount'] < (float)$b['amount'] ? 1 : -1);

        return response()->json($this->clean($return));
    }
}

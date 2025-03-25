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
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;

/**
 * Class BudgetController
 */
class CategoryController extends Controller
{
    use CleansChartData;
    use ValidatesUserGroupTrait;

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
        $start = $this->parameters->get('start');

        /** @var Carbon $end */
        $end        = $this->parameters->get('end');
        $accounts   = $this->accountRepos->getAccountsByType([AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value]);
        $currencies = [];
        $return     = [];

        // get journals for entire period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts)->withCategoryInformation();
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::RECONCILIATION->value]);
        $journals = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId              = (int) $journal['currency_id'];
            $currency                = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            $currencies[$currencyId] = $currency;
            $categoryName            = null === $journal['category_name'] ? (string) trans('firefly.no_category') : $journal['category_name'];
            $amount                  = app('steam')->positive($journal['amount']);
            $key                     = sprintf('%s-%s', $categoryName, $currency->code);
            // create arrays
            $return[$key] ??= [
                'label'                   => $categoryName,
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_name'           => $currency->name,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'period'                  => null,
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'amount'                  => '0',
            ];

            // add monies
            $return[$key]['amount'] = bcadd($return[$key]['amount'], $amount);
        }
        $return = array_values($return);

        // order by amount
        usort($return, static function (array $a, array $b) {
            return (float) $a['amount'] < (float) $b['amount'] ? 1 : -1;
        });

        return response()->json($this->clean($return));
    }
}

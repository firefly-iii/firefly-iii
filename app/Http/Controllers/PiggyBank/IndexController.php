<?php

/**
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\ObjectGroup\OrganisesObjectGroups;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use OrganisesObjectGroups;

    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-bullseye');

                $this->piggyRepos = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show overview of all piggy banks.
     *
     * TODO very complicated function.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function index()
    {
        $this->cleanupObjectGroups();
        $this->piggyRepos->resetOrder();
        $collection         = $this->piggyRepos->getPiggyBanks();
        $accounts           = [];

        /** @var Carbon $end */
        $end                = session('end', today(config('app.timezone'))->endOfMonth());

        // transform piggies using the transformer:
        $parameters         = new ParameterBag();
        $parameters->set('end', $end);

        // make piggy bank groups:
        $piggyBanks         = [];

        /** @var PiggyBankTransformer $transformer */
        $transformer        = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag());

        /** @var AccountTransformer $accountTransformer */
        $accountTransformer = app(AccountTransformer::class);
        $accountTransformer->setParameters($parameters);

        /** @var PiggyBank $piggy */
        foreach ($collection as $piggy) {
            $array                                    = $transformer->transform($piggy);
            $groupOrder                               = (int)$array['object_group_order'];
            // make group array if necessary:
            $piggyBanks[$groupOrder] ??= [
                'object_group_id'    => $array['object_group_id'] ?? 0,
                'object_group_title' => $array['object_group_title'] ?? trans('firefly.default_group_title_name'),
                'piggy_banks'        => [],
            ];

            $account                                  = $accountTransformer->transform($piggy->account);
            $accountId                                = (int)$account['id'];
            $array['attachments']                     = $this->piggyRepos->getAttachments($piggy);
            if (!array_key_exists($accountId, $accounts)) {
                // create new:
                $accounts[$accountId]            = $account;

                // add some interesting details:
                $accounts[$accountId]['left']    = $accounts[$accountId]['current_balance'];
                $accounts[$accountId]['saved']   = 0;
                $accounts[$accountId]['target']  = 0;
                $accounts[$accountId]['to_save'] = 0;
            }

            // calculate new interesting fields:
            $accounts[$accountId]['left']             -= $array['current_amount'];
            $accounts[$accountId]['saved']            += $array['current_amount'];
            $accounts[$accountId]['target']           += $array['target_amount'];
            $accounts[$accountId]['to_save']          += ($array['target_amount'] - $array['current_amount']);
            $array['account_name']                    = $account['name'];
            $piggyBanks[$groupOrder]['piggy_banks'][] = $array;
        }
        // do a bunch of summaries.
        $piggyBanks         = $this->makeSums($piggyBanks);

        ksort($piggyBanks);

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }

    private function makeSums(array $piggyBanks): array
    {
        $sums = [];
        foreach ($piggyBanks as $groupOrder => $group) {
            $groupId = $group['object_group_id'];
            foreach ($group['piggy_banks'] as $piggy) {
                $currencyId                                    = $piggy['currency_id'];
                $sums[$groupId][$currencyId] ??= [
                    'target'                  => '0',
                    'saved'                   => '0',
                    'left_to_save'            => '0',
                    'save_per_month'          => '0',
                    'currency_id'             => $currencyId,
                    'currency_code'           => $piggy['currency_code'],
                    'currency_symbol'         => $piggy['currency_symbol'],
                    'currency_decimal_places' => $piggy['currency_decimal_places'],
                ];
                // target_amount
                // current_amount
                // left_to_save
                // save_per_month
                $sums[$groupId][$currencyId]['target']         = bcadd($sums[$groupId][$currencyId]['target'], (string)$piggy['target_amount']);
                $sums[$groupId][$currencyId]['saved']          = bcadd($sums[$groupId][$currencyId]['saved'], (string)$piggy['current_amount']);
                $sums[$groupId][$currencyId]['left_to_save']   = bcadd($sums[$groupId][$currencyId]['left_to_save'], (string)$piggy['left_to_save']);
                $sums[$groupId][$currencyId]['save_per_month'] = bcadd($sums[$groupId][$currencyId]['save_per_month'], (string)$piggy['save_per_month']);
            }
        }
        foreach ($piggyBanks as $groupOrder => $group) {
            $groupId                         = $group['object_group_id'];
            $piggyBanks[$groupOrder]['sums'] = $sums[$groupId] ?? [];
        }

        return $piggyBanks;
    }

    /**
     * Set the order of a piggy bank.
     */
    public function setOrder(Request $request, PiggyBank $piggyBank): JsonResponse
    {
        $objectGroupTitle = (string)$request->get('objectGroupTitle');
        $newOrder         = (int)$request->get('order');
        $this->piggyRepos->setOrder($piggyBank, $newOrder);
        if ('' !== $objectGroupTitle) {
            $this->piggyRepos->setObjectGroup($piggyBank, $objectGroupTitle);
        }
        if ('' === $objectGroupTitle) {
            $this->piggyRepos->removeObjectGroup($piggyBank);
        }

        return response()->json(['data' => 'OK']);
    }
}

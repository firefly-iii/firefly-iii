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
use Illuminate\Support\Collection;
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
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
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

        /** @var Carbon $end */
        $end                = session('end', today(config('app.timezone'))->endOfMonth());

        // transform piggies using the transformer:
        $parameters         = new ParameterBag();
        $parameters->set('end', $end);


        /** @var AccountTransformer $accountTransformer */
        $accountTransformer = app(AccountTransformer::class);
        $accountTransformer->setParameters($parameters);

        // data
        $piggyBanks         = $this->groupPiggyBanks($collection);
        $accounts           = $this->collectAccounts($collection);
        $accounts           = $this->mergeAccountsAndPiggies($piggyBanks, $accounts);
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
                $sums[$groupId][$currencyId]['target']         = bcadd($sums[$groupId][$currencyId]['target'], (string) $piggy['target_amount']);
                $sums[$groupId][$currencyId]['saved']          = bcadd($sums[$groupId][$currencyId]['saved'], (string) $piggy['current_amount']);
                $sums[$groupId][$currencyId]['left_to_save']   = bcadd($sums[$groupId][$currencyId]['left_to_save'], (string) $piggy['left_to_save']);
                $sums[$groupId][$currencyId]['save_per_month'] = bcadd($sums[$groupId][$currencyId]['save_per_month'], (string) $piggy['save_per_month']);
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
        $objectGroupTitle = (string) $request->get('objectGroupTitle');
        $newOrder         = (int) $request->get('order');
        $this->piggyRepos->setOrder($piggyBank, $newOrder);
        if ('' !== $objectGroupTitle) {
            $this->piggyRepos->setObjectGroup($piggyBank, $objectGroupTitle);
        }
        if ('' === $objectGroupTitle) {
            $this->piggyRepos->removeObjectGroup($piggyBank);
        }

        return response()->json(['data' => 'OK']);
    }

    private function groupPiggyBanks(Collection $collection): array
    {
        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters(new ParameterBag());
        $piggyBanks  = [];

        /** @var PiggyBank $piggy */
        foreach ($collection as $piggy) {
            $array                                    = $transformer->transform($piggy);
            $groupOrder                               = (int) $array['object_group_order'];
            $piggyBanks[$groupOrder] ??= [
                'object_group_id'    => $array['object_group_id'] ?? 0,
                'object_group_title' => $array['object_group_title'] ?? trans('firefly.default_group_title_name'),
                'piggy_banks'        => [],
            ];
            $array['attachments']                     = $this->piggyRepos->getAttachments($piggy);

            // sum the total amount for the index.
            $piggyBanks[$groupOrder]['piggy_banks'][] = $array;
        }

        return $piggyBanks;
    }

    private function collectAccounts(Collection $collection): array
    {
        /** @var Carbon $end */
        $end                = session('end', today(config('app.timezone'))->endOfMonth());

        // transform piggies using the transformer:
        $parameters         = new ParameterBag();
        $parameters->set('end', $end);

        /** @var AccountTransformer $accountTransformer */
        $accountTransformer = app(AccountTransformer::class);
        $accountTransformer->setParameters($parameters);

        $return             = [];

        /** @var PiggyBank $piggy */
        foreach ($collection as $piggy) {
            $accounts = $piggy->accounts;

            /** @var Account $account */
            foreach ($accounts as $account) {
                $array     = $accountTransformer->transform($account);
                $accountId = (int) $array['id'];
                if (!array_key_exists($accountId, $return)) {
                    $return[$accountId]            = $array;

                    // add some interesting details:
                    $return[$accountId]['left']    = $return[$accountId]['current_balance'];
                    $return[$accountId]['saved']   = '0';
                    $return[$accountId]['target']  = '0';
                    $return[$accountId]['to_save'] = '0';
                }

                // calculate new interesting fields:
                //                $return[$accountId]['left']             -= $array['current_amount'];
                //                $return[$accountId]['saved']            += $array['current_amount'];
                //                $return[$accountId]['target']           += $array['target_amount'];
                //                $return[$accountId]['to_save']          += ($array['target_amount'] - $array['current_amount']);
                //                $return['account_name']                    = $account['name'];

            }
        }

        return $return;
    }

    private function mergeAccountsAndPiggies(array $piggyBanks, array $accounts): array
    {
        // @var array $piggyBank
        foreach ($piggyBanks as $group) {
            foreach ($group['piggy_banks'] as $piggyBank) {
                // loop all accounts in this piggy bank subtract the current amount from "left to save" in the $accounts array.
                /** @var array $piggyAccount */
                foreach ($piggyBank['accounts'] as $piggyAccount) {
                    $accountId = $piggyAccount['id'];
                    if (array_key_exists($accountId, $accounts)) {
                        $accounts[$accountId]['left']    = bcsub($accounts[$accountId]['left'], $piggyAccount['current_amount']);
                        $accounts[$accountId]['saved']   = bcadd($accounts[$accountId]['saved'], $piggyAccount['current_amount']);
                        $accounts[$accountId]['target']  = bcadd($accounts[$accountId]['target'], $piggyBank['target_amount']);
                        $accounts[$accountId]['to_save'] = bcadd($accounts[$accountId]['to_save'], bcsub($piggyBank['target_amount'], $piggyAccount['current_amount']));
                    }
                }
            }
        }

        return $accounts;
    }
}

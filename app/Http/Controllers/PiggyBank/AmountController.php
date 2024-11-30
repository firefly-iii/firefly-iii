<?php

/**
 * AmountController.php
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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class AmountController
 */
class AmountController extends Controller
{
    private AccountRepositoryInterface   $accountRepos;
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

                $this->piggyRepos   = app(PiggyBankRepositoryInterface::class);
                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Add money to piggy bank.
     *
     * @return Factory|View
     */
    public function add(PiggyBank $piggyBank)
    {
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, today(config('app.timezone')));
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $maxAmount     = $leftOnAccount;
        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $leftToSave = bcsub($piggyBank->target_amount, $savedSoFar);
            $maxAmount  = min($leftOnAccount, $leftToSave);
        }
        $currency      = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount', 'currency'));
    }

    /**
     * Add money to piggy bank (for mobile devices).
     *
     * @return Factory|View
     */
    public function addMobile(PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date          = session('end', today(config('app.timezone')));
        $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $date);
        $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank);
        $maxAmount     = $leftOnAccount;

        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $leftToSave = bcsub($piggyBank->target_amount, $savedSoFar);
            $maxAmount  = min($leftOnAccount, $leftToSave);
        }
        $currency      = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.add-mobile', compact('piggyBank', 'maxAmount', 'currency'));
    }

    /**
     * Add money to piggy bank.
     */
    public function postAdd(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $amount   = $request->get('amount') ?? '0';
        $currency = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();
        // if amount is negative, make positive and continue:
        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }
        if ($this->piggyRepos->canAddAmount($piggyBank, $amount)) {
            $this->piggyRepos->addAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string)trans(
                    'firefly.added_amount_to_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }

        app('log')->error('Cannot add '.$amount.' because canAddAmount returned false.');
        session()->flash(
            'error',
            (string)trans(
                'firefly.cannot_add_amount_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => e($piggyBank->name)]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * Remove money from piggy bank.
     */
    public function postRemove(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $amount   = $request->get('amount') ?? '0';
        $currency = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();
        // if amount is negative, make positive and continue:
        if (-1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }
        if ($this->piggyRepos->canRemoveAmount($piggyBank, $amount)) {
            $this->piggyRepos->removeAmount($piggyBank, $amount);
            session()->flash(
                'success',
                (string)trans(
                    'firefly.removed_amount_from_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }
        $amount   = (string)$request->get('amount');

        session()->flash(
            'error',
            (string)trans(
                'firefly.cannot_remove_from_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => e($piggyBank->name)]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * Remove money from piggy bank form.
     *
     * @return Factory|View
     */
    public function remove(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        $currency   = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.remove', compact('piggyBank', 'repetition', 'currency'));
    }

    /**
     * Remove money from piggy bank (for mobile devices).
     *
     * @return Factory|View
     */
    public function removeMobile(PiggyBank $piggyBank)
    {
        $repetition = $this->piggyRepos->getRepetition($piggyBank);
        $currency   = $this->accountRepos->getAccountCurrency($piggyBank->account) ?? app('amount')->getDefaultCurrency();

        return view('piggy-banks.remove-mobile', compact('piggyBank', 'repetition', 'currency'));
    }
}

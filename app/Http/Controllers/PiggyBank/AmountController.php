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
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
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
        $accounts   = [];
        $total      = '0';
        $totalSaved = $this->piggyRepos->getCurrentAmount($piggyBank);
        $leftToSave = bcsub($piggyBank->target_amount, $totalSaved);
        foreach ($piggyBank->accounts as $account) {
            $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $account, today(config('app.timezone'))->endOfDay());
            $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank, $account);
            $maxAmount     = 0 === bccomp($piggyBank->target_amount, '0') ? $leftToSave : min($leftOnAccount, $leftToSave);
            $accounts[]    = [
                'account'         => $account,
                'left_on_account' => $leftOnAccount,
                'saved_so_far'    => $savedSoFar,
                'left_to_save'    => $leftToSave,
                'max_amount'      => $maxAmount,
            ];
            $total         = bcadd($total, $leftOnAccount);
        }
        $total      = (float) $total; // intentional float.

        return view('piggy-banks.add', compact('piggyBank', 'accounts', 'total'));
    }

    /**
     * Add money to piggy bank (for mobile devices).
     *
     * @return Factory|View
     */
    public function addMobile(PiggyBank $piggyBank)
    {
        /** @var Carbon $date */
        $date     = session('end', today(config('app.timezone')));
        $accounts = [];
        $total    = '0';
        foreach ($piggyBank->accounts as $account) {
            $leftOnAccount = $this->piggyRepos->leftOnAccount($piggyBank, $account, $date);
            $savedSoFar    = $this->piggyRepos->getCurrentAmount($piggyBank, $account);
            $leftToSave    = bcsub($piggyBank->target_amount, $savedSoFar);
            $accounts[]    = [
                'account'         => $account,
                'left_on_account' => $leftOnAccount,
                'saved_so_far'    => $savedSoFar,
                'left_to_save'    => $leftToSave,
                'max_amount'      => 0 === bccomp($piggyBank->target_amount, '0') ? $leftOnAccount : min($leftOnAccount, $leftToSave),
            ];
            $total         = bcadd($total, $leftOnAccount);
        }

        return view('piggy-banks.add-mobile', compact('piggyBank', 'total', 'accounts'));
    }

    /**
     * Add money to piggy bank.
     */
    public function postAdd(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $data    = $request->all();
        $amounts = $data['amount'] ?? [];
        $total   = '0';
        Log::debug('Start with loop.');

        /** @var Account $account */
        foreach ($piggyBank->accounts as $account) {
            $amount        = (string) ($amounts[$account->id] ?? '0');
            if ('' === $amount || 0 === bccomp($amount, '0')) {
                continue;
            }
            if (-1 === bccomp($amount, '0')) {
                $amount = bcmul($amount, '-1');
            }

            // small check to see if the $amount is not more than the total "left to save" value
            $currentAmount = $this->piggyRepos->getCurrentAmount($piggyBank);
            $leftToSave    = 0 === bccomp($piggyBank->target_amount, '0') ? '0' : bcsub($piggyBank->target_amount, $currentAmount);
            if (bccomp($amount, $leftToSave) > 0 && 0 !== bccomp($leftToSave, '0')) {
                Log::debug(sprintf('Amount "%s" is more than left to save "%s". Using left to save.', $amount, $leftToSave));
                $amount = $leftToSave;
            }

            $canAddAmount  = $this->piggyRepos->canAddAmount($piggyBank, $account, $amount);
            if ($canAddAmount) {
                $this->piggyRepos->addAmount($piggyBank, $account, $amount);
                $total = bcadd($total, $amount);
            }
            $piggyBank->refresh();
        }
        if (0 !== bccomp($total, '0')) {
            session()->flash('success', (string) trans('firefly.added_amount_to_piggy', ['amount' => app('amount')->formatAnything($piggyBank->transactionCurrency, $total, false), 'name' => $piggyBank->name]));
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }
        app('log')->error(sprintf('Cannot add %s because canAddAmount returned false.', $total));
        session()->flash(
            'error',
            (string) trans(
                'firefly.cannot_add_amount_piggy',
                ['amount' => app('amount')->formatAnything($piggyBank->transactionCurrency, $total, false), 'name' => e($piggyBank->name)]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * Remove money from piggy bank.
     */
    public function postRemove(Request $request, PiggyBank $piggyBank): RedirectResponse
    {
        $amounts = $request->get('amount') ?? [];
        if (!is_array($amounts)) {
            $amounts = [];
        }
        $total   = '0';

        /** @var Account $account */
        foreach ($piggyBank->accounts as $account) {
            $amount = (string) ($amounts[$account->id] ?? '0');
            if ('' === $amount || 0 === bccomp($amount, '0')) {
                continue;
            }
            if (-1 === bccomp($amount, '0')) {
                $amount = bcmul($amount, '-1');
            }
            if ($this->piggyRepos->canRemoveAmount($piggyBank, $account, $amount)) {
                $this->piggyRepos->removeAmount($piggyBank, $account, $amount);
                $total = bcadd($total, $amount);
            }
        }
        if (0 !== bccomp($total, '0')) {
            session()->flash(
                'success',
                (string) trans(
                    'firefly.removed_amount_from_piggy',
                    ['amount' => app('amount')->formatAnything($piggyBank->transactionCurrency, $total, false), 'name' => $piggyBank->name]
                )
            );
            app('preferences')->mark();

            return redirect(route('piggy-banks.index'));
        }

        session()->flash(
            'error',
            (string) trans(
                'firefly.cannot_remove_from_piggy',
                ['amount' => app('amount')->formatAnything($piggyBank->transactionCurrency, $total, false), 'name' => e($piggyBank->name)]
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
        $accounts = [];
        foreach ($piggyBank->accounts as $account) {
            $accounts[] = [
                'account'      => $account,
                'saved_so_far' => $this->piggyRepos->getCurrentAmount($piggyBank, $account),
            ];
        }

        return view('piggy-banks.remove', compact('piggyBank', 'accounts'));
    }

    /**
     * Remove money from piggy bank (for mobile devices).
     *
     * @return Factory|View
     */
    public function removeMobile(PiggyBank $piggyBank)
    {
        $accounts = [];
        foreach ($piggyBank->accounts as $account) {
            $accounts[] = [
                'account'      => $account,
                'saved_so_far' => $this->piggyRepos->getCurrentAmount($piggyBank, $account),
            ];
        }

        return view('piggy-banks.remove-mobile', compact('piggyBank', 'accounts'));
    }
}

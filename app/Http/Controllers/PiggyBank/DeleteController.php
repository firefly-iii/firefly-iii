<?php

/**
 * DeleteController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
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
     * Delete a piggy bank.
     *
     * @return Factory|View
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = (string) trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]);

        // put previous url in session
        $this->rememberPreviousUrl('piggy-banks.delete.url');

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * Destroy the piggy bank.
     */
    public function destroy(PiggyBank $piggyBank): RedirectResponse
    {
        session()->flash('success', (string) trans('firefly.deleted_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();
        $this->piggyRepos->destroy($piggyBank);

        return redirect($this->getPreviousUrl('piggy-banks.delete.url'));
    }
}

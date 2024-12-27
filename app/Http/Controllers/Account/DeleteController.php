<?php

/**
 * DeleteController.php
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

namespace FireflyIII\Http\Controllers\Account;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * DeleteController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string) trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete account screen.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function delete(Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        $typeName    = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));
        $subTitle    = (string) trans(sprintf('firefly.delete_%s_account', $typeName), ['name' => $account->name]);
        $accountList = app('expandedform')->makeSelectListWithEmpty($this->repository->getAccountsByType([$account->accountType->type]));
        $objectType  = $typeName;
        unset($accountList[$account->id]);

        // put previous url in session
        $this->rememberPreviousUrl('accounts.delete.url');

        return view('accounts.delete', compact('account', 'subTitle', 'accountList', 'objectType'));
    }

    /**
     * Delete the account.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        $type     = $account->accountType->type;
        $typeName = config(sprintf('firefly.shortNamesByFullName.%s', $type));
        $name     = $account->name;
        $moveTo   = $this->repository->find((int) $request->get('move_account_before_delete'));

        $this->repository->destroy($account, $moveTo);

        $request->session()->flash('success', (string) trans(sprintf('firefly.%s_deleted', $typeName), ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('accounts.delete.url'));
    }
}

<?php
/**
 * DeleteController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;

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
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete account screen.
     *
     * @param Account $account
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Account $account)
    {
        $typeName    = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $subTitle    = (string)trans('firefly.delete_' . $typeName . '_account', ['name' => $account->name]);
        $accountList = app('expandedform')->makeSelectListWithEmpty($this->repository->getAccountsByType([$account->accountType->type]));
        $what        = $typeName;
        unset($accountList[$account->id]);

        // put previous url in session
        $this->rememberPreviousUri('accounts.delete.uri');

        return view('accounts.delete', compact('account', 'subTitle', 'accountList', 'what'));
    }

    /**
     * Delete the account.
     *
     * @param Request $request
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Account $account)
    {
        $type     = $account->accountType->type;
        $typeName = config('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;
        $moveTo   = $this->repository->findNull((int)$request->get('move_account_before_delete'));

        $this->repository->destroy($account, $moveTo);

        $request->session()->flash('success', (string)trans('firefly.' . $typeName . '_deleted', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('accounts.delete.uri'));
    }

}

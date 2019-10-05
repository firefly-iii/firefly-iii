<?php
/**
 * DeleteController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Http\Request;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    use UserNavigation;

    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * DeleteController constructor.
     * @codeCoverageIgnore
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
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $typeName    = config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type));
        $subTitle    = (string)trans(sprintf('firefly.delete_%s_account', $typeName), ['name' => $account->name]);
        $accountList = app('expandedform')->makeSelectListWithEmpty($this->repository->getAccountsByType([$account->accountType->type]));
        $objectType  = $typeName;
        unset($accountList[$account->id]);

        // put previous url in session
        $this->rememberPreviousUri('accounts.delete.uri');

        return view('accounts.delete', compact('account', 'subTitle', 'accountList', 'objectType'));
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
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $type     = $account->accountType->type;
        $typeName = config(sprintf('firefly.shortNamesByFullName.%s', $type));
        $name     = $account->name;
        $moveTo   = $this->repository->findNull((int)$request->get('move_account_before_delete'));

        $this->repository->destroy($account, $moveTo);

        $request->session()->flash('success', (string)trans(sprintf('firefly.%s_deleted', $typeName), ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('accounts.delete.uri'));
    }

}

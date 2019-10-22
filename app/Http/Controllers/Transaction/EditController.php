<?php
/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Transaction;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use UserNavigation;
    /**
     * EditController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $maxFileSize = app('steam')->phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = app('steam')->phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);


        app('view')->share('uploadSize', $uploadSize);

        // some useful repositories:
        $this->middleware(
            static function ($request, $next) {

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }


    /**
     * @param TransactionGroup $transactionGroup
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(TransactionGroup $transactionGroup)
    {
        app('preferences')->mark();

        if (!$this->isEditableGroup($transactionGroup)) {
            return $this->redirectGroupToAccount($transactionGroup); // @codeCoverageIgnore
        }

        /** @var AccountRepositoryInterface $repository */
        $repository           = app(AccountRepositoryInterface::class);
        $allowedOpposingTypes = config('firefly.allowed_opposing_types');
        $accountToTypes       = config('firefly.account_to_transaction');
        $defaultCurrency      = app('amount')->getDefaultCurrency();
        $cash                 = $repository->getCashAccount();
        $previousUri          = $this->rememberPreviousUri('transactions.edit.uri');
        $parts                = parse_url($previousUri);
        $previousUri          = sprintf('%s://%s/%s', $parts['scheme'], $parts['host'], $parts['path']);


        return view('transactions.edit', compact('cash', 'transactionGroup', 'allowedOpposingTypes', 'accountToTypes', 'defaultCurrency', 'previousUri'));
    }

}

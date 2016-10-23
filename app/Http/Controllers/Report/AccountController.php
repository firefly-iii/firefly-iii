<?php
/**
 * AccountController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Illuminate\Support\Collection;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class AccountController extends Controller
{

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function accountReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $accountTasker = app(AccountTaskerInterface::class);
        $accountReport = $accountTasker->getAccountReport($start, $end, $accounts);

        return view('reports.partials.accounts', compact('accountReport'));
    }
}

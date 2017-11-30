<?php
/**
 * HomeController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Session;

/**
 * Class HomeController.
 */
class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $title         = strval(trans('firefly.administration'));
        $mainTitleIcon = 'fa-hand-spock-o';

        return view('admin.index', compact('title', 'mainTitleIcon'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function testMessage(Request $request)
    {
        $ipAddress = $request->ip();
        Log::debug(sprintf('Now in testMessage() controller. IP is %s', $ipAddress));
        event(new AdminRequestedTestMessage(auth()->user(), $ipAddress));
        Session::flash('info', strval(trans('firefly.send_test_triggered')));

        return redirect(route('admin.index'));
    }
}

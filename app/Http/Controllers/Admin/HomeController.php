<?php
/**
 * HomeController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Session;

/**
 * Class HomeController
 *
 * @package FireflyIII\Http\Controllers\Admin
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

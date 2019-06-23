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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use FireflyIII\User;
use Illuminate\Http\Request;
use Log;

/**
 * Class HomeController.
 */
class HomeController extends Controller
{
    /**
     * ConfigurationController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(IsDemoUser::class)->except(['index']);
        $this->middleware(IsSandStormUser::class)->except(['index']);
    }

    /**
     * Index of the admin.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        Log::channel('audit')->info('User visits admin index.');
        $title         = (string)trans('firefly.administration');
        $mainTitleIcon = 'fa-hand-spock-o';
        $sandstorm     = 1 === (int)getenv('SANDSTORM');

        return view('admin.index', compact('title', 'mainTitleIcon', 'sandstorm'));
    }

    /**
     * Send a test message to the admin.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function testMessage(Request $request)
    {
        Log::channel('audit')->info('User sends test message.');
        /** @var User $user */
        $user      = auth()->user();
        $ipAddress = $request->ip();
        Log::debug(sprintf('Now in testMessage() controller. IP is %s', $ipAddress));
        event(new AdminRequestedTestMessage($user, $ipAddress));
        session()->flash('info', (string)trans('firefly.send_test_triggered'));

        return redirect(route('admin.index'));
    }
}

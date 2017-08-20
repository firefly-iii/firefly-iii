<?php
/**
 * LinkController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use View;

/**
 * Class LinkController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class LinkController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('title', strval(trans('firefly.administration')));
                View::share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
    }

    /**
     *
     */
    public function index()
    {
        $subTitle     = trans('firefly.journal_link_configuration');
        $subTitleIcon = 'fa-link';

        return view('admin.link.index', compact('subTitle', 'subTitleIcon'));
    }

}
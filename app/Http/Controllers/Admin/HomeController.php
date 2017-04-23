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


use FireflyIII\Http\Controllers\Controller;

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

}

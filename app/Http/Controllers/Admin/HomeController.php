<?php
declare(strict_types = 1);

/**
 * HomeController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
     * @return mixed
     */
    public function index()
    {
        $title         = strval(trans('firefly.administration'));
        $mainTitleIcon = 'fa-hand-spock-o';

        return view('admin.index', compact('title', 'mainTitleIcon'));
    }

}

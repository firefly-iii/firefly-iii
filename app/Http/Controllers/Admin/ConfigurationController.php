<?php
/**
 * ConfigurationController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use View;

/**
 * Class ConfigurationController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class ConfigurationController extends Controller
{
    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        View::share('title', strval(trans('firefly.administration')));
        View::share('mainTitleIcon', 'fa-hand-spock-o');

    }

    /**
     * @return View
     */
    public function index()
    {
        $subTitle     = strval(trans('firefly.instance_configuration'));
        $subTitleIcon = 'fa-wrench';

        return view('admin.configuration.index', compact('subTitle', 'subTitleIcon'));

    }

}

<?php
/**
 * RuleController.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Http\Requests;
use View;

/**
 * Class RuleController
 *
 * @package FireflyIII\Http\Controllers
 */
class RuleController extends Controller
{
    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.rules'));
        View::share('mainTitleIcon', 'fa-random');
    }


    /**
     * @return View
     */
    public function index()
    {
        $ruleGroups = Auth::user()->ruleGroups()->with('rules')->get();

        return view('rules.index', compact('ruleGroups'));
    }
}

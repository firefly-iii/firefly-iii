<?php

namespace FireflyIII\Http\Controllers;

use View;
/**
 * Class RuleGroupController
 * @package FireflyIII\Http\Controllers
 */
class RuleGroupController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.rules'));
        View::share('mainTitleIcon', 'fa-random');
    }

}
<?php
/**
 * ConfigurationController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Admin;


use Config;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ConfigurationRequest;
use FireflyIII\Support\Facades\FireflyConfig;
use Preferences;
use Redirect;
use Session;
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

        // all available configuration and their default value in case
        // they don't exist yet.
        $singleUserMode = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;

        return view('admin.configuration.index', compact('subTitle', 'subTitleIcon', 'singleUserMode'));

    }

    /**
     * @param ConfigurationRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ConfigurationRequest $request)
    {
        // get config values:
        $singleUserMode = intval($request->get('single_user_mode')) === 1 ? true : false;

        // store config values
        FireflyConfig::set('single_user_mode', $singleUserMode);

        // flash message
        Session::flash('success', strval(trans('firefly.configuration_updated')));
        Preferences::mark();

        return Redirect::route('admin.configuration.index');
    }

}

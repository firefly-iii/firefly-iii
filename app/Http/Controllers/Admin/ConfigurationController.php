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


        $this->middleware(
            function ($request, $next) {
                View::share('title', strval(trans('firefly.administration')));
                View::share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );

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
        $singleUserMode     = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $mustConfirmAccount = FireflyConfig::get('must_confirm_account', config('firefly.configuration.must_confirm_account'))->data;
        $isDemoSite         = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;

        return view('admin.configuration.index', compact('subTitle', 'subTitleIcon', 'singleUserMode', 'mustConfirmAccount', 'isDemoSite'));

    }

    /**
     * @param ConfigurationRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ConfigurationRequest $request)
    {
        // get config values:
        $data = $request->getConfigurationData();

        // store config values
        FireflyConfig::set('single_user_mode', $data['single_user_mode']);
        FireflyConfig::set('must_confirm_account', $data['must_confirm_account']);
        FireflyConfig::set('is_demo_site', $data['is_demo_site']);

        // flash message
        Session::flash('success', strval(trans('firefly.configuration_updated')));
        Preferences::mark();

        return Redirect::route('admin.configuration.index');
    }

}

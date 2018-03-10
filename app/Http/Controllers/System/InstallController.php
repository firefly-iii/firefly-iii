<?php
/**
 * InstallController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\System;


use Artisan;
use FireflyIII\Http\Controllers\Controller;
use Laravel\Passport\Passport;
use Log;
use phpseclib\Crypt\RSA;

/**
 * Class InstallController
 */
class InstallController extends Controller
{
    /**
     * InstallController constructor.
     */
    public function __construct()
    {
        // empty on purpose.
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function index()
    {
        return view('install.index');
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function keys()
    {
        // create keys manually because for some reason the passport namespace
        // does not exist
        $rsa  = new RSA();
        $keys = $rsa->createKey(4096);

        list($publicKey, $privateKey) = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if ((file_exists($publicKey) || file_exists($privateKey))) {
            return response()->json(['OK']);
        }

        file_put_contents($publicKey, array_get($keys, 'publickey'));
        file_put_contents($privateKey, array_get($keys, 'privatekey'));

        return response()->json(['OK']);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function migrate()
    {
        Log::debug('Am now calling migrate routine...');
        Artisan::call('migrate', ['--seed' => true, '--force' => true]);
        Log::debug(Artisan::output());

        return response()->json(['OK']);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function upgrade()
    {
        Log::debug('Am now calling upgrade database routine...');
        Artisan::call('firefly:upgrade-database');
        Log::debug(Artisan::output());

        return response()->json(['OK']);
    }

}
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
use Exception;
use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Passport;
use Log;
use phpseclib\Crypt\RSA;

/**
 * @codeCoverageIgnore
 * Class InstallController
 */
class InstallController extends Controller
{
    /** @var string */
    public const FORBIDDEN_ERROR = 'Internal PHP function "proc_close" is disabled for your installation. Auto-migration is not possible.';
    /** @var string */
    public const BASEDIR_ERROR = 'Firefly III cannot execute the upgrade commands. It is not allowed to because of an open_basedir restriction.';
    /** @var string */
    public const OTHER_ERROR = 'An unknown error prevented Firefly III from executing the upgrade commands. Sorry.';
    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */
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
        if ($this->hasForbiddenFunctions()) {
            return response()->json(['error' => true, 'message' => self::FORBIDDEN_ERROR]);
        }
        // create keys manually because for some reason the passport namespace
        // does not exist
        $rsa  = new RSA();
        $keys = $rsa->createKey(4096);

        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if (file_exists($publicKey) || file_exists($privateKey)) {
            return response()->json(['error' => false, 'message' => 'OK']);
        }

        file_put_contents($publicKey, array_get($keys, 'publickey'));
        file_put_contents($privateKey, array_get($keys, 'privatekey'));

        return response()->json(['error' => false, 'message' => 'OK']);
    }

    /**
     * @return JsonResponse
     */
    public function migrate(): JsonResponse
    {
        if ($this->hasForbiddenFunctions()) {
            return response()->json(['error' => true, 'message' => self::FORBIDDEN_ERROR]);
        }

        try {
            Log::debug('Am now calling migrate routine...');
            Artisan::call('migrate', ['--seed' => true, '--force' => true]);
            Log::debug(Artisan::output());
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            if (strpos($e->getMessage(), 'open_basedir restriction in effect')) {
                return response()->json(['error' => true, 'message' => self::BASEDIR_ERROR]);
            }

            return response()->json(['error' => true, 'message' => self::OTHER_ERROR]);
        }


        return response()->json(['error' => false, 'message' => 'OK']);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function upgrade(): JsonResponse
    {
        if ($this->hasForbiddenFunctions()) {
            return response()->json(['error' => true, 'message' => self::FORBIDDEN_ERROR]);
        }
        try {
            Log::debug('Am now calling upgrade database routine...');
            Artisan::call('firefly:upgrade-database');
            Log::debug(Artisan::output());
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            if (strpos($e->getMessage(), 'open_basedir restriction in effect')) {
                return response()->json(['error' => true, 'message' => self::BASEDIR_ERROR]);
            }

            return response()->json(['error' => true, 'message' => self::OTHER_ERROR]);
        }

        return response()->json(['error' => false, 'message' => 'OK']);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(): JsonResponse
    {
        if ($this->hasForbiddenFunctions()) {
            return response()->json(['error' => true, 'message' => self::FORBIDDEN_ERROR]);
        }
        try {
            Log::debug('Am now calling verify database routine...');
            Artisan::call('firefly:verify');
            Log::debug(Artisan::output());
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            if (strpos($e->getMessage(), 'open_basedir restriction in effect')) {
                return response()->json(['error' => true, 'message' => self::BASEDIR_ERROR]);
            }

            return response()->json(['error' => true, 'message' => self::OTHER_ERROR]);
        }

        return response()->json(['error' => false, 'message' => 'OK']);
    }

    /**
     * @return bool
     */
    private function hasForbiddenFunctions(): bool
    {
        $list      = ['proc_close'];
        $forbidden = explode(',', ini_get('disable_functions'));
        $trimmed   = array_map(
            function (string $value) {
                return trim($value);
            }, $forbidden
        );
        foreach ($list as $entry) {
            if (\in_array($entry, $trimmed, true)) {
                Log::error('Method "%s" is FORBIDDEN, so the console command cannot be executed.');

                return true;
            }
        }

        return false;
    }

}

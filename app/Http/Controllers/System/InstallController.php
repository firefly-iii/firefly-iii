<?php

/**
 * InstallController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Passport\Passport;
use phpseclib3\Crypt\RSA;

use function Safe\file_put_contents;

/**
 * Class InstallController
 */
class InstallController extends Controller
{
    use GetConfigurationData;

    public const string BASEDIR_ERROR   = 'Firefly III cannot execute the upgrade commands. It is not allowed to because of an open_basedir restriction.';
    public const string FORBIDDEN_ERROR = 'Internal PHP function "proc_close" is disabled for your installation. Auto-migration is not possible.';
    public const string OTHER_ERROR     = 'An unknown error prevented Firefly III from executing the upgrade commands. Sorry.';
    private string $lastError;
    private array  $upgradeCommands;

    /**
     * InstallController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // empty on purpose.
        $this->upgradeCommands = [
            // there are 5 initial commands
            // Check 4 places: InstallController, Docker image, UpgradeDatabase, composer.json
            'migrate'                            => ['--seed' => true, '--force' => true],
            'generate-keys'                      => [], // an exception :(
            'firefly-iii:upgrade-database'       => [],
            'firefly-iii:set-latest-version'     => ['--james-is-cool' => true],
            'firefly-iii:verify-security-alerts' => [],
        ];

        $this->lastError       = '';
    }

    /**
     * Show index.
     *
     * @return Factory|View
     */
    public function index()
    {
        app('view')->share('FF_VERSION', config('firefly.version'));
        // index will set FF3 version.
        app('fireflyconfig')->set('ff3_version', (string) config('firefly.version'));

        // set new DB version.
        app('fireflyconfig')->set('db_version', (int) config('firefly.db_version'));

        return view('install.index');
    }

    public function runCommand(Request $request): JsonResponse
    {
        $requestIndex = (int) $request->get('index');
        $response     = [
            'hasNextCommand' => false,
            'done'           => true,
            'previous'       => null,
            'error'          => false,
            'errorMessage'   => null,
        ];

        app('log')->debug(sprintf('Will now run commands. Request index is %d', $requestIndex));
        $indexes      = array_keys($this->upgradeCommands);
        if (array_key_exists($requestIndex, $indexes)) {
            $command                    = $indexes[$requestIndex];
            $parameters                 = $this->upgradeCommands[$command];
            app('log')->debug(sprintf('Will now execute command "%s" with parameters', $command), $parameters);

            try {
                $result = $this->executeCommand($command, $parameters);
            } catch (FireflyException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                if (str_contains($e->getMessage(), 'open_basedir restriction in effect')) {
                    $this->lastError = self::BASEDIR_ERROR;
                }
                $result          = false;
                $this->lastError = sprintf('%s %s', self::OTHER_ERROR, $e->getMessage());
            }
            if (false === $result) {
                $response['errorMessage'] = $this->lastError;
                $response['error']        = true;

                return response()->json($response);
            }
            $response['hasNextCommand'] = array_key_exists($requestIndex + 1, $indexes);
            $response['previous']       = $command;
        }

        return response()->json($response);
    }

    /**
     * @throws FireflyException
     */
    private function executeCommand(string $command, array $args): bool
    {
        app('log')->debug(sprintf('Will now call command %s with args.', $command), $args);

        try {
            if ('generate-keys' === $command) {
                $this->keys();
            }
            if ('generate-keys' !== $command) {
                Artisan::call($command, $args);
                app('log')->debug(Artisan::output());
            }
        } catch (Exception $e) { // intentional generic exception
            throw new FireflyException($e->getMessage(), 0, $e);
        }
        // clear cache as well.
        Cache::clear();
        app('preferences')->mark();

        return true;
    }

    /**
     * Create specific RSA keys.
     */
    public function keys(): void
    {
        $key                      = RSA::createKey(4096);

        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if (file_exists($publicKey) || file_exists($privateKey)) {
            return;
        }

        file_put_contents($publicKey, (string) $key->getPublicKey());
        file_put_contents($privateKey, $key->toString('PKCS1'));
    }
}

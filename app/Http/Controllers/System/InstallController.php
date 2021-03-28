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

use Artisan;
use Cache;
use Exception;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Laravel\Passport\Passport;
use Log;
use phpseclib3\Crypt\RSA;

/**
 * Class InstallController
 *
 * @codeCoverageIgnore
 */
class InstallController extends Controller
{
    use GetConfigurationData;

    public const FORBIDDEN_ERROR = 'Internal PHP function "proc_close" is disabled for your installation. Auto-migration is not possible.';
    public const BASEDIR_ERROR   = 'Firefly III cannot execute the upgrade commands. It is not allowed to because of an open_basedir restriction.';
    public const OTHER_ERROR     = 'An unknown error prevented Firefly III from executing the upgrade commands. Sorry.';
    private string $lastError;
    private array  $upgradeCommands;
    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */

    /**
     * InstallController constructor.
     */
    public function __construct()
    {
        // empty on purpose.
        $this->upgradeCommands = [
            // there are 3 initial commands
            'migrate'                                  => ['--seed' => true, '--force' => true],
            'firefly-iii:decrypt-all'                  => [],
            'firefly-iii:restore-oauth-keys'           => [],
            'generate-keys'                            => [], // an exception :(

            // upgrade commands
            'firefly-iii:transaction-identifiers'      => [],
            'firefly-iii:migrate-to-groups'            => [],
            'firefly-iii:account-currencies'           => [],
            'firefly-iii:transfer-currencies'          => [],
            'firefly-iii:other-currencies'             => [],
            'firefly-iii:migrate-notes'                => [],
            'firefly-iii:migrate-attachments'          => [],
            'firefly-iii:bills-to-rules'               => [],
            'firefly-iii:bl-currency'                  => [],
            'firefly-iii:cc-liabilities'               => [],
            'firefly-iii:back-to-journals'             => [],
            'firefly-iii:rename-account-meta'          => [],
            'firefly-iii:migrate-recurrence-meta'      => [],
            'firefly-iii:migrate-tag-locations'        => [],
            'firefly-iii:migrate-recurrence-type'      => [],

            // verify commands
            'firefly-iii:fix-piggies'                  => [],
            'firefly-iii:create-link-types'            => [],
            'firefly-iii:create-access-tokens'         => [],
            'firefly-iii:remove-bills'                 => [],
            'firefly-iii:enable-currencies'            => [],
            'firefly-iii:fix-transfer-budgets'         => [],
            'firefly-iii:fix-uneven-amount'            => [],
            'firefly-iii:delete-zero-amount'           => [],
            'firefly-iii:delete-orphaned-transactions' => [],
            'firefly-iii:delete-empty-journals'        => [],
            'firefly-iii:delete-empty-groups'          => [],
            'firefly-iii:fix-account-types'            => [],
            'firefly-iii:fix-account-order'            => [],
            'firefly-iii:rename-meta-fields'           => [],
            'firefly-iii:fix-ob-currencies'            => [],
            'firefly-iii:fix-long-descriptions'        => [],
            'firefly-iii:fix-recurring-transactions'   => [],
            'firefly-iii:unify-group-accounts'         => [],
            'firefly-iii:fix-transaction-types'        => [],

            // final command to set latest version in DB
            'firefly-iii:set-latest-version'           => ['--james-is-cool' => true],
        ];

        $this->lastError = '';
    }

    /**
     * Show index.
     *
     * @return Factory|View
     */
    public function index()
    {
        // index will set FF3 version.
        app('fireflyconfig')->set('ff3_version', (string)config('firefly.version'));

        // set new DB version.
        app('fireflyconfig')->set('db_version', (int)config('firefly.db_version'));

        return prefixView('install.index');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function runCommand(Request $request): JsonResponse
    {
        $requestIndex = (int)$request->get('index');
        $response     = [
            'hasNextCommand' => false,
            'done'           => true,
            'next'           => 0,
            'previous'       => null,
            'error'          => false,
            'errorMessage'   => null,
        ];

        Log::debug(sprintf('Will now run commands. Request index is %d', $requestIndex));
        $index = 0;
        /**
         * @var string $command
         * @var array  $args
         */
        foreach ($this->upgradeCommands as $command => $args) {
            Log::debug(sprintf('Current command is "%s", index is %d', $command, $index));
            if ($index < $requestIndex) {
                Log::debug('Will not execute.');
                $index++;
                continue;
            }
            $result = $this->executeCommand($command, $args);
            if (false === $result) {
                $response['errorMessage'] = $this->lastError;
                $response['error']        = true;

                return response()->json($response);
            }
            $index++;
            $response['hasNextCommand'] = true;
            $response['previous']       = $command;
        }
        $response['next'] = $index;

        return response()->json($response);
    }

    /**
     * @param string $command
     * @param array  $args
     *
     * @return bool
     */
    private function executeCommand(string $command, array $args): bool
    {
        Log::debug(sprintf('Will now call command %s with args.', $command), $args);
        try {
            if ('generate-keys' === $command) {
                $this->keys();
            }
            if ('generate-keys' !== $command) {
                Artisan::call($command, $args);
                Log::debug(Artisan::output());
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            if (strpos($e->getMessage(), 'open_basedir restriction in effect')) {
                $this->lastError = self::BASEDIR_ERROR;

                return false;
            }
            $this->lastError = sprintf('%s %s', self::OTHER_ERROR, $e->getMessage());

            return false;
        }
        // clear cache as well.
        Cache::clear();
        Preferences::mark();

        return true;
    }

    /**
     * Create specific RSA keys.
     */
    public function keys(): void
    {
        // switch on PHP version.
        $result = version_compare(phpversion(), '8.0');
        Log::info(sprintf('PHP version is %s', $result));
        if (-1 === $result) {
            Log::info('Will run PHP7 code.');
            // PHP 7
            $rsa  = new \phpseclib\Crypt\RSA;
            $keys = $rsa->createKey(4096);
        }

        if ($result >= 0) {
            Log::info('Will run PHP8 code.');
            // PHP 8
            $keys = RSA::createKey(4096);
        }

        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if (file_exists($publicKey) || file_exists($privateKey)) {
            return;
        }

        file_put_contents($publicKey, Arr::get($keys, 'publickey'));
        file_put_contents($privateKey, Arr::get($keys, 'privatekey'));
    }
}

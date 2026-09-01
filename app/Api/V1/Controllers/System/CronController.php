<?php

/*
 * CronController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\System;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\System\CronRequest;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Binder\CLIToken;
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\Support\Http\Controllers\CronRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

/**
 * Class CronController
 */
final class CronController extends Controller
{
    use CronRunner;

    public function cron(CronRequest $request, #[SensitiveParameter] string $cliToken): JsonResponse
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $result     = CLIToken::routeBinder($cliToken, $request->route());
        $config     = $request->getAll();
        $user       = CLIToken::findUserByToken($cliToken);
        $users      = new Collection();
        if (null !== $user) {
            $users->push($user);
        }

        // no user matches the cliToken but that is OK if the token matches the static token (we check again)
        if (null === $user && hash_equals($result, (string) config('firefly.static_cron_token')) && 32 === strlen(config('firefly.static_cron_token'))) {
            Log::info('No user matches the cliToken but the static token matches, so we will continue for ALL users.');
            $users = $repository->allAvailable();
        }
        Log::debug(sprintf('Now in %s', __METHOD__));
        Log::debug(sprintf('Date is %s', $config['date']->toIsoString()));
        $return     = [
            'recurring_transactions' => [],
            'auto_budgets'           => [],
            'exchange_rates'         => [],
            'bill_notifications'     => [],
            'webhooks'               => [],
        ];
        foreach ($users as $current) {
            $return['recurring_transactions'][] = $this->runRecurring($current, $config['force'], $config['date']);
            $return['auto_budgets'][]           = $this->runAutoBudget($current, $config['force'], $config['date']);
            if (true === AppConfiguration::get('enable_external_rates', config('cer.download_enabled'))->data) {
                $return['exchange_rates'][] = $this->exchangeRatesCronJob($current, $config['force'], $config['date']);
            }
            $return['bill_notifications'][]     = $this->billWarningCronJob($current, $config['force'], $config['date']);
            $return['webhooks'][]               = $this->webhookCronJob($current, $config['force'], $config['date']);
        }

        return response()->api($return);
    }
}

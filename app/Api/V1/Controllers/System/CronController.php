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
use FireflyIII\Support\Http\Controllers\CronRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class CronController
 */
class CronController extends Controller
{
    use CronRunner;

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/about/getCron
     */
    public function cron(CronRequest $request): JsonResponse
    {
        $config                           = $request->getAll();

        Log::debug(sprintf('Now in %s', __METHOD__));
        Log::debug(sprintf('Date is %s', $config['date']->toIsoString()));
        $return                           = [];
        $return['recurring_transactions'] = $this->runRecurring($config['force'], $config['date']);
        $return['auto_budgets']           = $this->runAutoBudget($config['force'], $config['date']);
        if (true === config('cer.download_enabled')) {
            $return['exchange_rates'] = $this->exchangeRatesCronJob($config['force'], $config['date']);
        }
        $return['bill_notifications']     = $this->billWarningCronJob($config['force'], $config['date']);

        return response()->api($return);
    }
}

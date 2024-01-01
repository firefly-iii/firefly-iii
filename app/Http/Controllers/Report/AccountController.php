<?php

/**
 * AccountController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class AccountController.
 */
class AccountController extends Controller
{
    /**
     * Show partial overview for account balances.
     *
     * @throws FireflyException
     */
    public function general(Collection $accounts, Carbon $start, Carbon $end): string
    {
        // chart properties for cache:
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var AccountTaskerInterface $accountTasker */
        $accountTasker = app(AccountTaskerInterface::class);
        $accountReport = $accountTasker->getAccountReport($accounts, $start, $end);

        try {
            $result = view('reports.partials.accounts', compact('accountReport'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render reports.partials.accounts: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        $cache->store($result);

        return $result;
    }
}

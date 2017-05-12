<?php
/**
 * AccountController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class AccountController extends Controller
{

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function general(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var AccountTaskerInterface $accountTasker */
        $accountTasker = app(AccountTaskerInterface::class);
        $accountReport = $accountTasker->getAccountReport($accounts, $start, $end);

        $result = view('reports.partials.accounts', compact('accountReport'))->render();
        $cache->store($result);

        return $result;
    }
}

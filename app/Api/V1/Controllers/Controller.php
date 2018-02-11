<?php
/**
 * Controller.php
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

namespace FireflyIII\Api\V1\Controllers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var ParameterBag */
    protected $parameters;

    /**
     * Controller constructor.
     *
     * @throws FireflyException
     */
    public function __construct()
    {
        // is site a demo site?
        $isDemoSite = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;

        // do not expose API on demo site:
        if (true === $isDemoSite) {
            throw new FireflyException('The API is not available on the demo site.');
        }

        // get global parameters
        $this->parameters = $this->getParameters();
    }

    /**
     * @return ParameterBag
     */
    private function getParameters(): ParameterBag
    {
        $bag  = new ParameterBag;
        $page = (int)request()->get('page');
        if ($page === 0) {
            $page = 1;
        }
        $bag->set('page', $page);

        $start     = request()->get('start');
        $startDate = null;
        if (!is_null($start)) {
            try {
                $startDate = new Carbon($start);
            } catch (InvalidDateException $e) {
                // don't care
            }
        }
        $bag->set('start', $startDate);

        $end     = request()->get('end');
        $endDate = null;
        if (!is_null($end)) {
            try {
                $endDate = new Carbon($end);
            } catch (InvalidDateException $e) {
                // don't care
            }
        }
        $bag->set('end', $endDate);

        $type = request()->get('type') ?? 'all';
        $bag->set('type', $type);

        return $bag;

    }
}

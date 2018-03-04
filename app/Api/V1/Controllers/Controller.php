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
 * @codeCoverageIgnore
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
     * @return string
     */
    protected function buildParams(): string
    {
        $return = '?';
        $params = [];
        foreach ($this->parameters as $key => $value) {
            if($key === 'page') {
                continue;
            }
            if ($value instanceof Carbon) {
                $params[$key] = $value->format('Y-m-d');
            }
            if (!$value instanceof Carbon) {
                $params[$key] = $value;
            }
        }
        $return .= http_build_query($params);
        if (strlen($return) === 1) {
            return '';
        }

        return $return;
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

        // some date fields:
        $dates = ['start', 'end', 'date'];
        foreach ($dates as $field) {
            $date = request()->get($field);
            $obj  = null;
            if (!is_null($date)) {
                try {
                    $obj = new Carbon($date);
                } catch (InvalidDateException $e) {
                    // don't care
                }
            }
            $bag->set($field, $obj);
        }

        return $bag;

    }
}

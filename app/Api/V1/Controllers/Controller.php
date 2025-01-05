<?php

/**
 * Controller.php
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

namespace FireflyIII\Api\V1\Controllers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Controller.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected const string CONTENT_TYPE    = 'application/vnd.api+json';

    /** @var array<int, string> */
    protected array        $allowedSort;
    protected ParameterBag $parameters;
    protected bool        $convertToNative = false;
    protected TransactionCurrency $defaultCurrency;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        // get global parameters
        $this->allowedSort = config('firefly.allowed_sort_parameters');
        $this->middleware(
            function ($request, $next) {
                $this->parameters = $this->getParameters();
                if (auth()->check()) {
                    $language              = Steam::getLanguage();
                    $this->convertToNative = Amount::convertToNative();
                    $this->defaultCurrency = Amount::getDefaultCurrency();
                    app()->setLocale($language);

                }

                return $next($request);
            }
        );
    }

    /**
     * Method to grab all parameters from the URL.
     */
    private function getParameters(): ParameterBag
    {
        $bag      = new ParameterBag();
        $page     = (int) request()->get('page');
        if ($page < 1) {
            $page = 1;
        }
        if ($page > 2 ** 16) {
            $page = 2 ** 16;
        }
        $bag->set('page', $page);

        // some date fields:
        $dates    = ['start', 'end', 'date'];
        foreach ($dates as $field) {
            $date = null;

            try {
                $date = request()->query->get($field);
            } catch (BadRequestException $e) {
                app('log')->error(sprintf('Request field "%s" contains a non-scalar value. Value set to NULL.', $field));
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $value = null;
            }
            $obj  = null;
            if (null !== $date) {
                try {
                    $obj = Carbon::parse((string) $date);
                } catch (InvalidFormatException $e) {
                    // don't care
                    app('log')->warning(
                        sprintf(
                            'Ignored invalid date "%s" in API controller parameter check: %s',
                            substr((string) $date, 0, 20),
                            $e->getMessage()
                        )
                    );
                }
            }
            $bag->set($field, $obj);
        }

        // integer fields:
        $integers = ['limit'];
        foreach ($integers as $integer) {
            try {
                $value = request()->query->get($integer);
            } catch (BadRequestException $e) {
                app('log')->error(sprintf('Request field "%s" contains a non-scalar value. Value set to NULL.', $integer));
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                $value = null;
            }
            if (null !== $value) {
                $bag->set($integer, (int) $value);
            }
            if (null === $value
                && 'limit' === $integer // @phpstan-ignore-line
                && auth()->check()) {
                // set default for user:
                /** @var User $user */
                $user     = auth()->user();

                /** @var Preference $pageSize */
                $pageSize = (int) app('preferences')->getForUser($user, 'listPageSize', 50)->data;
                $bag->set($integer, $pageSize);
            }
        }

        // sort fields:
        return $this->getSortParameters($bag);
    }

    private function getSortParameters(ParameterBag $bag): ParameterBag
    {
        $sortParameters = [];

        try {
            $param = (string) request()->query->get('sort');
        } catch (BadRequestException $e) {
            app('log')->error('Request field "sort" contains a non-scalar value. Value set to NULL.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $param = '';
        }
        if ('' === $param) {
            return $bag;
        }
        $parts          = explode(',', $param);
        foreach ($parts as $part) {
            $part      = trim($part);
            $direction = 'asc';
            if ('-' === $part[0]) {
                $part      = substr($part, 1);
                $direction = 'desc';
            }
            if (in_array($part, $this->allowedSort, true)) {
                $sortParameters[] = [$part, $direction];
            }
        }
        $bag->set('sort', $sortParameters);

        return $bag;
    }

    /**
     * Method to help build URL's.
     */
    final protected function buildParams(): string
    {
        $return = '?';
        $params = [];
        foreach ($this->parameters as $key => $value) {
            if ('page' === $key) {
                continue;
            }
            if ($value instanceof Carbon) {
                $params[$key] = $value->format('Y-m-d');

                continue;
            }
            $params[$key] = $value;
        }

        return $return.http_build_query($params);
    }

    final protected function getManager(): Manager
    {
        // create some objects:
        $manager = new Manager();
        $baseUrl = request()->getSchemeAndHttpHost().'/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        return $manager;
    }
}

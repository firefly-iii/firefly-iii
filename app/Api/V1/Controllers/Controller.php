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
use FireflyIII\Exceptions\BadHttpHeaderException;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use FireflyIII\Transformers\V2\AbstractTransformer;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
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
    use ValidatesUserGroupTrait;

    protected const string CONTENT_TYPE      = 'application/vnd.api+json';
    protected const string JSON_CONTENT_TYPE = 'application/json';

    /** @var array<int, string> */
    protected array        $allowedSort;
    protected ParameterBag $parameters;
    protected bool        $convertToNative   = false;
    protected array $accepts                 = ['application/json', 'application/vnd.api+json'];
    protected TransactionCurrency $nativeCurrency;

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
                    $this->nativeCurrency  = Amount::getNativeCurrency();
                    app()->setLocale($language);
                }


                // filter down what this endpoint accepts.
                if (!$request->accepts($this->accepts)) {
                    throw new BadHttpHeaderException(sprintf('Sorry, Accept header "%s" is not something this endpoint can provide.', $request->header('Accept')));
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
                $value = (int) $value;
                if ($value < 1) {
                    $value = 1;
                }
                if ($value > 2 ** 16) {
                    $value = 2 ** 16;
                }

                $bag->set($integer, $value);
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

    final protected function jsonApiList(string $key, LengthAwarePaginator $paginator, AbstractTransformer $transformer): array
    {
        $manager  = new Manager();
        $baseUrl  = sprintf('%s/api/v1/', request()->getSchemeAndHttpHost());

        // TODO add stuff to path?

        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $objects  = $paginator->getCollection();

        // the transformer, at this point, needs to collect information that ALL items in the collection
        // require, like meta-data and stuff like that, and save it for later.
        $objects  = $transformer->collectMetaData($objects);
        $paginator->setCollection($objects);

        $resource = new FractalCollection($objects, $transformer, $key);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return $manager->createData($resource)->toArray();
    }

    /**
     * Returns a JSON API object and returns it.
     *
     * @param array<int, mixed>|Model $object
     */
    final protected function jsonApiObject(string $key, array|Model $object, AbstractTransformer $transformer): array
    {
        // create some objects:
        $manager  = new Manager();
        $baseUrl  = sprintf('%s/api/v1', request()->getSchemeAndHttpHost());
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $transformer->collectMetaData(new Collection([$object]));

        $resource = new Item($object, $transformer, $key);

        return $manager->createData($resource)->toArray();
    }
}

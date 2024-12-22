<?php

/*
 * Controller.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use FireflyIII\Transformers\V2\AbstractTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Controller extends BaseController
{
    use ValidatesUserGroupTrait;

    protected const string CONTENT_TYPE = 'application/vnd.api+json';
    protected ParameterBag $parameters;
    protected array $acceptedRoles      = [UserRoleEnum::READ_ONLY];

    public function __construct()
    {
        $this->middleware(
            function ($request, $next) {
                $this->parameters = $this->getParameters();

                return $next($request);
            }
        );
    }

    /**
     * TODO duplicate from V1 controller
     * Method to grab all parameters from the URL.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getParameters(): ParameterBag
    {
        $bag      = new ParameterBag();
        $bag->set('limit', 50);

        try {
            $page = (int)request()->get('page');
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            $page = 1;
        }

        $integers = ['limit', 'administration'];
        $dates    = ['start', 'end', 'date'];

        if ($page < 1) {
            $page = 1;
        }
        if ($page > pow(2,16)) {
            $page = pow(2,16);
        }
        $bag->set('page', $page);

        // some date fields:
        foreach ($dates as $field) {
            $date = null;
            $obj  = null;

            try {
                $date = request()->query->get($field);
            } catch (BadRequestException $e) {
                app('log')->error(sprintf('Request field "%s" contains a non-scalar value. Value set to NULL.', $field));
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
            if (null !== $date) {
                try {
                    $obj = Carbon::parse((string)$date, config('app.timezone'));
                } catch (InvalidDateException|InvalidFormatException $e) {
                    // don't care
                    app('log')->warning(sprintf('Ignored invalid date "%s" in API v2 controller parameter check: %s', substr((string)$date, 0, 20), $e->getMessage()));
                }
                // out of range? set to null.
                if (null !== $obj && ($obj->year <= 1900 || $obj->year > 2099)) {
                    app('log')->warning(sprintf('Refuse to use date "%s" in API v2 controller parameter check: %s', $field, $obj->toAtomString()));
                    $obj = null;
                }
            }
            if (null !== $date && 'end' === $field) {
                $obj->endOfDay();
            }
            $bag->set($field, $obj);
        }

        // integer fields:
        foreach ($integers as $integer) {
            try {
                $value = request()->query->get($integer);
            } catch (BadRequestException $e) {
                app('log')->error(sprintf('Request field "%s" contains a non-scalar value. Value set to NULL.', $integer));
                app('log')->error($e->getMessage());
                $value = null;
            }
            if (null !== $value) {
                $bag->set($integer, (int)$value);
            }
            if (null === $value && 'limit' === $integer && auth()->check()) {
                // set default for user:
                $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
                $bag->set($integer, $pageSize);
            }
        }

        // sort fields:
        //   return $this->getSortParameters($bag);

        return $bag;
    }

    final protected function jsonApiList(string $key, LengthAwarePaginator $paginator, AbstractTransformer $transformer): array
    {
        $manager  = new Manager();
        $baseUrl  = request()->getSchemeAndHttpHost().'/api/v2';

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
        $baseUrl  = request()->getSchemeAndHttpHost().'/api/v2';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $transformer->collectMetaData(new Collection([$object]));

        $resource = new Item($object, $transformer, $key);

        return $manager->createData($resource)->toArray();
    }
}

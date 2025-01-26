<?php

/*
 * PreferencesController.php
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

namespace FireflyIII\Api\V1\Controllers\User;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\User\PreferenceStoreRequest;
use FireflyIII\Api\V1\Requests\User\PreferenceUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Preference;
use FireflyIII\Transformers\PreferenceTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class PreferencesController
 */
class PreferencesController extends Controller
{
    public const string DATE_FORMAT  = 'Y-m-d';
    public const string RESOURCE_KEY = 'preferences';

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/preferences/listPreference
     *
     * List all of them.
     *
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        $collection  = app('preferences')->all();
        $manager     = $this->getManager();
        $count       = $collection->count();
        $pageSize    = $this->parameters->get('limit');
        $preferences = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($preferences, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.preferences.index').$this->buildParams());

        /** @var PreferenceTransformer $transformer */
        $transformer = app(PreferenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($preferences, $transformer, self::RESOURCE_KEY);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/preferences/getPreference
     *
     * Return a single preference by name.
     */
    public function show(Preference $preference): JsonResponse
    {
        $manager     = $this->getManager();

        if ('currencyPreference' === $preference->name) {
            throw new FireflyException('Please use api/v1/currencies/native instead.');
        }

        /** @var PreferenceTransformer $transformer */
        $transformer = app(PreferenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($preference, $transformer, 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/preferences/storePreference
     *
     * @throws FireflyException
     */
    public function store(PreferenceStoreRequest $request): JsonResponse
    {
        $manager     = $this->getManager();
        $data        = $request->getAll();

        if ('currencyPreference' === $data['name']) {
            throw new FireflyException('Please use api/v1/currencies/default instead.');
        }

        $pref        = app('preferences')->set($data['name'], $data['data']);

        /** @var PreferenceTransformer $transformer */
        $transformer = app(PreferenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($pref, $transformer, 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/preferences/updatePreference
     *
     * @throws FireflyException
     */
    public function update(PreferenceUpdateRequest $request, Preference $preference): JsonResponse
    {
        if ('currencyPreference' === $preference->name) {
            throw new FireflyException('Please use api/v1/currencies/native instead.');
        }

        $manager     = $this->getManager();
        $data        = $request->getAll();
        $pref        = app('preferences')->set($preference->name, $data['data']);

        /** @var PreferenceTransformer $transformer */
        $transformer = app(PreferenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($pref, $transformer, 'preferences');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}

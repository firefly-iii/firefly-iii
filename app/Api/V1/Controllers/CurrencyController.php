<?php
/**
 * CurrencyController.php
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

use FireflyIII\Api\V1\Requests\CurrencyRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\CurrencyTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class CurrencyController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrencyController extends Controller
{
    /** @var CurrencyRepositoryInterface The currency repository */
    private $repository;
    /** @var UserRepositoryInterface The user repository */
    private $userRepository;

    /**
     * CurrencyRepository constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var CurrencyRepositoryInterface repository */
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  TransactionCurrency $currency
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function delete(TransactionCurrency $currency): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            // access denied:
            throw new FireflyException('No access to method, user is not owner.'); // @codeCoverageIgnore
        }
        if (!$this->repository->canDeleteCurrency($currency)) {
            throw new FireflyException('No access to method, currency is in use.'); // @codeCoverageIgnore
        }
        $this->repository->destroy($currency);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $collection = $this->repository->get();
        $count      = $collection->count();
        // slice them:
        $currencies = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator  = new LengthAwarePaginator($currencies, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.index') . $this->buildParams());


        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        $resource = new FractalCollection($currencies, new CurrencyTransformer($this->parameters), 'currencies');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Show a currency.
     *
     * @param Request             $request
     * @param TransactionCurrency $currency
     *
     * @return JsonResponse
     */
    public function show(Request $request, TransactionCurrency $currency): JsonResponse
    {
        $manager = new Manager();
        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        $resource = new Item($currency, new CurrencyTransformer($this->parameters), 'currencies');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new currency.
     *
     * @param CurrencyRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(CurrencyRequest $request): JsonResponse
    {
        $currency = $this->repository->store($request->getAll());

        if (null !== $currency) {
            if (true === $request->boolean('default')) {
                app('preferences')->set('currencyPreference', $currency->code);
                app('preferences')->mark();
            }
            $manager = new Manager();
            $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
            $manager->setSerializer(new JsonApiSerializer($baseUrl));
            $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
            $this->parameters->set('defaultCurrency', $defaultCurrency);

            $resource = new Item($currency, new CurrencyTransformer($this->parameters), 'currencies');

            return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
        }
        throw new FireflyException('Could not store new currency.'); // @codeCoverageIgnore

    }


    /**
     * Update a currency.
     *
     * @param CurrencyRequest     $request
     * @param TransactionCurrency $currency
     *
     * @return JsonResponse
     */
    public function update(CurrencyRequest $request, TransactionCurrency $currency): JsonResponse
    {
        $data     = $request->getAll();
        $currency = $this->repository->update($currency, $data);

        if (true === $request->boolean('default')) {
            app('preferences')->set('currencyPreference', $currency->code);
            app('preferences')->mark();
        }

        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        $resource = new Item($currency, new CurrencyTransformer($this->parameters), 'currencies');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}

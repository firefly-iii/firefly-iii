<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionCurrency;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\CurrencyTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    use AccountFilter, TransactionFilter;

    private CurrencyRepositoryInterface $repository;

    /**
     * CurrencyRepository constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var CurrencyRepositoryInterface repository */
                $this->repository = app(CurrencyRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $collection = $this->repository->getAll();
        $count      = $collection->count();
        // slice them:
        $currencies = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator  = new LengthAwarePaginator($currencies, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.index') . $this->buildParams());


        $manager         = $this->getManager();
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        /** @var CurrencyTransformer $transformer */
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($currencies, $transformer, 'currencies');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show a currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(TransactionCurrency $currency): JsonResponse
    {
        $manager         = $this->getManager();
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        /** @var CurrencyTransformer $transformer */
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($currency, $transformer, 'currencies');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show a currency.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function showDefault(): JsonResponse
    {
        $manager  = $this->getManager();
        $currency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $currency);

        /** @var CurrencyTransformer $transformer */
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($currency, $transformer, 'currencies');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
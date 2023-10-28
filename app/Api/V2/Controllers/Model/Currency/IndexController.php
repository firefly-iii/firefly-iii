<?php
declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers\Model\Currency;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\V2\CurrencyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private CurrencyRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * TODO This endpoint is not yet documented.
     *
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $bills       = $this->repository->getAll();
        $pageSize    = $this->parameters->get('limit');
        $count       = $bills->count();
        $bills       = $bills->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator   = new LengthAwarePaginator($bills, $count, $pageSize, $this->parameters->get('page'));
        $transformer = new CurrencyTransformer();
        $transformer->setParameters($this->parameters); // give params to transformer

        return response()
            ->json($this->jsonApiList('currencies', $paginator, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE);
    }

}

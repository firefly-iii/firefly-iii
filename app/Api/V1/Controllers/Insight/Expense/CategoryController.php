<?php


namespace FireflyIII\Api\V1\Controllers\Insight\Expense;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\ExpenseRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class CategoryController
 */
class CategoryController extends Controller
{
    private OperationsRepositoryInterface $opsRepository;
    private CategoryRepositoryInterface   $repository;
    private NoCategoryRepositoryInterface $noRepository;

    /**
     * AccountController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository    = app(CategoryRepositoryInterface::class);
                $this->noRepository  = app(NoCategoryRepositoryInterface::class);
                $user                = auth()->user();
                $this->opsRepository->setUser($user);
                $this->repository->setUser($user);
                $this->noRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param ExpenseRequest $request
     *
     * @return JsonResponse
     */
    public function category(ExpenseRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $categories    = $request->getCategories();
        $assetAccounts = $request->getAssetAccounts();
        $result        = [];
        if (0 === $categories->count()) {
            $categories = $this->repository->getCategories();
        }
        /** @var Category $category */
        foreach ($categories as $category) {
            $expenses = $this->opsRepository->sumExpenses($start, $end, $assetAccounts, new Collection([$category]));
            /** @var array $expense */
            foreach ($expenses as $expense) {
                $result[] = [
                    'id'               => (string)$category->id,
                    'name'             => $category->name,
                    'difference'       => $expense['sum'],
                    'difference_float' => (float)$expense['sum'],
                    'currency_id'      => (string)$expense['currency_id'],
                    'currency_code'    => $expense['currency_code'],
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * @param ExpenseRequest $request
     *
     * @return JsonResponse
     */
    public function noCategory(ExpenseRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $assetAccounts = $request->getAssetAccounts();
        $result        = [];
        $expenses      = $this->noRepository->sumExpenses($start, $end, $assetAccounts);
        /** @var array $expense */
        foreach ($expenses as $expense) {
            $result[] = [
                'difference'       => $expense['sum'],
                'difference_float' => (float)$expense['sum'],
                'currency_id'      => (string)$expense['currency_id'],
                'currency_code'    => $expense['currency_code'],
            ];
        }

        return response()->json($result);

    }
}
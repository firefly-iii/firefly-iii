<?php
declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Data\Bulk;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\Bulk\MoveTransactionsRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    private AccountRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * @param MoveTransactionsRequest $request
     *
     * @return JsonResponse
     */
    public function moveTransactions(MoveTransactionsRequest $request): JsonResponse
    {
        $accountIds  = $request->getAll();
        $original    = $this->repository->findNull($accountIds['original_account']);
        $destination = $this->repository->findNull($accountIds['destination_account']);

        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->moveTransactions($original, $destination);

        return response()->json([], 204);

    }

}

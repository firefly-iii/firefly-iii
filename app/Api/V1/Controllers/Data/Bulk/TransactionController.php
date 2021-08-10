<?php

namespace FireflyIII\Api\V1\Controllers\Data\Bulk;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\Bulk\TransactionRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 *
 * Endpoint to update transactions by submitting
 * (optional) a "where" clause and an "update"
 * clause.
 *
 * Because this is a security nightmare waiting to happen validation
 * is pretty strict.
 */
class TransactionController extends Controller
{
    private AccountRepositoryInterface $repository;

    /**
     *
     */
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
     * @param TransactionRequest $request
     *
     * @return JsonResponse
     */
    public function update(TransactionRequest $request): JsonResponse
    {
        $query  = $request->getAll();
        $params = $query['query'];
        // this deserves better code, but for now a loop of basic if-statements
        // to respond to what is in the $query.
        // this is OK because only one thing can be in the query at the moment.
        if ($this->updatesTransactionAccount($params)) {
            $original    = $this->repository->find((int)$params['where']['source_account_id']);
            $destination = $this->repository->find((int)$params['update']['destination_account_id']);

            /** @var AccountDestroyService $service */
            $service = app(AccountDestroyService::class);
            $service->moveTransactions($original, $destination);
        }

        return response()->json([], 204);
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function updatesTransactionAccount(array $params): bool
    {
        return array_key_exists('source_account_id', $params['where']) && array_key_exists('destination_account_id', $params['update']);
    }

}
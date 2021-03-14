<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionLink;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

class DestroyController extends Controller
{

    private LinkTypeRepositoryInterface $repository;


    /**
     * TransactionLinkController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->repository = app(LinkTypeRepositoryInterface::class);

                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param TransactionJournalLink $link
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroy(TransactionJournalLink $link): JsonResponse
    {
        $this->repository->destroyLink($link);

        return response()->json([], 204);
    }

}
<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionLinkType;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    use TransactionFilter;

    private LinkTypeRepositoryInterface $repository;
    private UserRepositoryInterface     $userRepository;

    /**
     * LinkTypeController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                 = auth()->user();
                $this->repository     = app(LinkTypeRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }


    /**
     * Delete the resource.
     *
     * @param LinkType $linkType
     *
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    public function destroy(LinkType $linkType): JsonResponse
    {
        if (false === $linkType->editable) {
            throw new FireflyException('200020: Link type cannot be changed.');
        }
        $this->repository->destroy($linkType);

        return response()->json([], 204);
    }

}
<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionLinkType;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionLinkType\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\LinkTypeTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
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
     * Store new object.
     *
     * @param StoreRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            throw new FireflyException('200005: You need the "owner" role to do this.'); // @codeCoverageIgnore
        }
        $data = $request->getAll();
        // if currency ID is 0, find the currency by the code:
        $linkType = $this->repository->store($data);
        $manager  = $this->getManager();

        /** @var LinkTypeTransformer $transformer */
        $transformer = app(LinkTypeTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($linkType, $transformer, 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}
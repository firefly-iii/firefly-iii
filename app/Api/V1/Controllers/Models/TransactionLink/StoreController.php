<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionLink;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionLink\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

class StoreController extends Controller
{
    use TransactionFilter;
    private JournalRepositoryInterface $journalRepository;
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

                $this->repository        = app(LinkTypeRepositoryInterface::class);
                $this->journalRepository = app(JournalRepositoryInterface::class);

                $this->repository->setUser($user);
                $this->journalRepository->setUser($user);

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
        $manager = $this->getManager();
        $data    = $request->getAll();
        $inward  = $this->journalRepository->findNull($data['inward_id'] ?? 0);
        $outward = $this->journalRepository->findNull($data['outward_id'] ?? 0);
        if (null === $inward || null === $outward) {
            throw new FireflyException('200024: Source or destination does not exist.');
        }
        $data['direction'] = 'inward';

        $journalLink = $this->repository->storeLink($data, $inward, $outward);

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
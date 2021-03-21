<?php


namespace FireflyIII\Api\V1\Controllers\Models\TransactionLink;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionLink\UpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

class UpdateController extends Controller
{
    private JournalRepositoryInterface  $journalRepository;
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
     * Update object.
     *
     * @param UpdateRequest          $request
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     * @throws FireflyException
     *
     * TODO generates query exception when link exists.
     */
    public function update(UpdateRequest $request, TransactionJournalLink $journalLink): JsonResponse
    {
        $manager     = $this->getManager();
        $data        = $request->getAll();
        $journalLink = $this->repository->updateLink($journalLink, $data);

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}
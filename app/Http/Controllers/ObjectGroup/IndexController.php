<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\ObjectGroup;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use Illuminate\Http\Request;
use Log;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private ObjectGroupRepositoryInterface $repository;

    /**
     * IndexController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-envelope-o');
                app('view')->share('title', (string) trans('firefly.object_groups_page_title'));
                $this->repository = app(ObjectGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $this->repository->sort();
        $subTitle     = (string) trans('firefly.object_groups_index');
        $objectGroups = $this->repository->get();

        return view('object-groups.index', compact('subTitle', 'objectGroups'));
    }

    /**
     * @param ObjectGroup $objectGroup
     */
    public function setOrder(Request $request, ObjectGroup $objectGroup)
    {
        Log::debug(sprintf('Found object group #%d "%s"', $objectGroup->id, $objectGroup->title));
        $newOrder = (int) $request->get('order');
        $this->repository->setOrder($objectGroup, $newOrder);

        return response()->json([]);
    }

}

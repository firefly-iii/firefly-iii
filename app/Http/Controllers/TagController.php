<?php
/**
 * TagController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Session;
use View;

/**
 * Class TagController
 *
 * Remember: a balancingAct takes at most one expense and one transfer.
 *           an advancePayment takes at most one expense, infinite deposits and NO transfers.
 *
 *  transaction can only have one advancePayment OR balancingAct.
 *  Other attempts to put in such a tag are blocked.
 *  also show an error when editing a tag and it becomes either
 *  of these two types. Or rather, block editing of the tag.
 *
 * @package FireflyIII\Http\Controllers
 */
class TagController extends Controller
{

    /** @var  TagRepositoryInterface */
    protected $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('hideTags', true);
        $this->redirectUri = route('tags.index');

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(TagRepositoryInterface::class);
                View::share('title', strval(trans('firefly.tags')));
                View::share('mainTitleIcon', 'fa-tags');

                return $next($request);
            }
        );
    }

    /**
     * Create a new tag.
     *
     * @return View
     */
    public function create()
    {
        $subTitle     = trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        // put previous url in session if not redirect from store (not "create another").
        if (session('tags.create.fromStore') !== true) {
            $this->rememberPreviousUri('tags.create.uri');
        }
        Session::forget('tags.create.fromStore');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'create');

        return view('tags.create', compact('subTitle', 'subTitleIcon', 'apiKey'));
    }

    /**
     * Delete a tag
     *
     * @param Tag $tag
     *
     * @return View
     */
    public function delete(Tag $tag)
    {
        $subTitle = trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]);

        // put previous url in session
        $this->rememberPreviousUri('tags.delete.uri');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'delete');

        return view('tags.delete', compact('tag', 'subTitle'));
    }

    /**
     * @param Tag $tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Tag $tag)
    {

        $tagName = $tag->tag;
        $this->repository->destroy($tag);

        Session::flash('success', strval(trans('firefly.deleted_tag', ['tag' => $tagName])));
        Preferences::mark();

        return redirect($this->getPreviousUri('tags.delete.uri'));
    }

    /**
     * Edit a tag
     *
     * @param Tag $tag
     *
     * @return View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = trans('firefly.edit_tag', ['tag' => $tag->tag]);
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('tags.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('tags.edit.uri');
        }
        Session::forget('tags.edit.fromUpdate');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'edit');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon', 'apiKey'));
    }

    /**
     * View all tags
     *
     * @param TagRepositoryInterface $repository
     *
     * @return View
     */
    public function index(TagRepositoryInterface $repository)
    {
        // start with oldest tag
        $oldestTag = $repository->oldestTag();
        /** @var Carbon $start */
        $start = new Carbon;
        if (!is_null($oldestTag)) {
            /** @var Carbon $start */
            $start = $oldestTag->date;
        }
        if (is_null($oldestTag)) {
            /** @var Carbon $start */
            $start = clone(session('first'));
        }

        $now               = new Carbon;
        $clouds            = [];
        $clouds['no-date'] = $repository->tagCloud(null);
        while ($now > $start) {
            $year          = $now->year;
            $clouds[$year] = $repository->tagCloud($year);

            $now->subYear();
        }
        $count = $repository->count();

        return view('tags.index', compact('clouds', 'count'));
    }

    /**
     * @param Request                $request
     * @param TagRepositoryInterface $repository
     * @param Tag                    $tag
     * @param string                 $moment
     *
     * @return View
     */
    public function show(Request $request, TagRepositoryInterface $repository, Tag $tag, string $moment = '')
    {
        // default values:
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');
        $path         = route('tags.show', [$tag->id]);

        // prep for "all" view.
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
            $start    = $repository->firstUseDate($tag);
            $end      = new Carbon;
            $path     = route('tags.show', [$tag->id, 'all']);
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.journals_in_period_for_tag',
                ['tag'   => $tag->tag,
                 'start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getPeriodOverview($tag);
            $path     = route('tags.show', [$tag->id, $moment]);
        }

        // prep for current period
        if (strlen($moment) === 0) {
            /** @var Carbon $start */
            $start = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            /** @var Carbon $end */
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview($tag);
            $subTitle = trans(
                'firefly.journals_in_period_for_tag',
                ['tag' => $tag->tag, 'start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setTag($tag)->withBudgetInformation()->withCategoryInformation()->removeFilter(InternalTransferFilter::class);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath($path);

        $sums = $repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('apiKey', 'tag', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'journals', 'start', 'end', 'moment'));
    }

    /**
     * @param TagFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TagFormRequest $request)
    {
        $data = $request->collectTagData();
        $this->repository->store($data);

        Session::flash('success', strval(trans('firefly.created_tag', ['tag' => $data['tag']])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('tags.create.fromStore', true);

            return redirect(route('tags.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('tags.create.uri'));

    }

    /**
     * @param TagFormRequest $request
     * @param Tag            $tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TagFormRequest $request, Tag $tag)
    {
        $data = $request->collectTagData();
        $this->repository->update($tag, $data);

        Session::flash('success', strval(trans('firefly.updated_tag', ['tag' => $data['tag']])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('tags.edit.fromUpdate', true);

            return redirect(route('tags.edit', [$tag->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('tags.edit.uri'));
    }

    /**
     * @param Tag $tag
     *
     * @return Collection
     */
    private function getPeriodOverview(Tag $tag): Collection
    {
        // get first and last tag date from tag:
        $range = Preferences::get('viewRange', '1M')->data;
        $start = Navigation::startOfPeriod($this->repository->firstUseDate($tag), $range);
        $end   = Navigation::startOfPeriod($this->repository->lastUseDate($tag), $range);
        // properties for entries with their amounts.
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('tag.entries');
        $cache->addProperty($tag->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $collection = new Collection;

        // while end larger or equal to start
        while ($end >= $start) {
            $currentEnd = Navigation::endOfPeriod($end, $range);

            // get expenses and what-not in this period and this tag.
            $arr = [
                'string' => $end->format('Y-m-d'),
                'name'   => Navigation::periodShow($end, $range),
                'date'   => clone $end,
                'spent'  => $this->repository->spentInperiod($tag, $end, $currentEnd),
                'earned' => $this->repository->earnedInperiod($tag, $end, $currentEnd),
            ];
            $collection->push($arr);

            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        $cache->store($collection);

        return $collection;

    }


}

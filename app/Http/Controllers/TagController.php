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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
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

    /** @var array */
    public $tagOptions = [];

    /** @var  TagRepositoryInterface */
    protected $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('hideTags', true);

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(TagRepositoryInterface::class);
                $this->tagOptions = [
                    'nothing'        => trans('firefly.regular_tag'),
                    'balancingAct'   => trans('firefly.balancing_act'),
                    'advancePayment' => trans('firefly.advance_payment'),
                ];


                View::share('title', strval(trans('firefly.tags')));
                View::share('mainTitleIcon', 'fa-tags');
                View::share('tagOptions', $this->tagOptions);


                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        $subTitle     = trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        $preFilled = [
            'tagMode' => 'nothing',
        ];
        if (!$request->old('tagMode')) {
            Session::flash('preFilled', $preFilled);
        }
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
     * @param Tag $tag
     *
     * @return View
     */
    public function delete(Tag $tag)
    {
        $subTitle = trans('breadcrumbs.delete_tag', ['tag' => e($tag->tag)]);

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

        Session::flash('success', strval(trans('firefly.deleted_tag', ['tag' => e($tagName)])));
        Preferences::mark();

        return redirect($this->getPreviousUri('tags.delete.uri'));
    }

    /**
     * @param Tag $tag
     *
     * @return View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = trans('firefly.edit_tag', ['tag' => $tag->tag]);
        $subTitleIcon = 'fa-tag';
        $apiKey       = env('GOOGLE_MAPS_API_KEY', '');

        /*
         * Default tag options (again)
         */
        $tagOptions = $this->tagOptions;

        /*
         * Can this tag become another type?
         */
        $allowAdvance        = Tag::tagAllowAdvance($tag);
        $allowToBalancingAct = Tag::tagAllowBalancing($tag);

        // edit tag options:
        if ($allowAdvance === false) {
            unset($tagOptions['advancePayment']);
        }
        if ($allowToBalancingAct === false) {
            unset($tagOptions['balancingAct']);
        }


        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('tags.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('tags.edit.uri');
        }
        Session::forget('tags.edit.fromUpdate');
        Session::flash('gaEventCategory', 'tags');
        Session::flash('gaEventAction', 'edit');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon', 'tagOptions', 'apiKey'));
    }

    /**
     *
     */
    public function index()
    {
        $title         = 'Tags';
        $mainTitleIcon = 'fa-tags';
        $types         = ['nothing', 'balancingAct', 'advancePayment'];

        // loop each types and get the tags, group them by year.
        $collection = [];
        foreach ($types as $type) {

            /** @var Collection $tags */
            $tags = auth()->user()->tags()->where('tagMode', $type)->orderBy('date', 'ASC')->get();
            $tags = $tags->sortBy(
                function (Tag $tag) {
                    $date = !is_null($tag->date) ? $tag->date->format('Ymd') : '000000';


                    return strtolower($date . $tag->tag);
                }
            );

            /** @var Tag $tag */
            foreach ($tags as $tag) {

                $year           = is_null($tag->date) ? trans('firefly.no_year') : $tag->date->year;
                $monthFormatted = is_null($tag->date) ? trans('firefly.no_month') : $tag->date->formatLocalized($this->monthFormat);

                $collection[$type][$year][$monthFormatted][] = $tag;
            }
        }

        return view('tags.index', compact('title', 'mainTitleIcon', 'types', 'collection'));
    }

    /**
     * @param Request                   $request
     * @param JournalCollectorInterface $collector
     * @param Tag                       $tag
     *
     * @return View
     */
    public function show(Request $request, JournalCollectorInterface $collector, Tag $tag)
    {
        $start        = clone session('start', Carbon::now()->startOfMonth());
        $end          = clone session('end', Carbon::now()->endOfMonth());
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $periods      = $this->getPeriodOverview($tag);

        // use collector:
        $collector->setAllAssetAccounts()
                  ->setLimit($pageSize)->setPage($page)->setTag($tag)->withOpposingAccount()->disableInternalFilter()
                  ->withBudgetInformation()->withCategoryInformation()->setRange($start, $end);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('tags/show/' . $tag->id);

        $sum = $journals->sum(
            function (Transaction $transaction) {
                return $transaction->transaction_amount;
            }
        );

        return view('tags.show', compact('tag', 'periods', 'subTitle', 'subTitleIcon', 'journals', 'sum', 'start', 'end'));
    }

    /**
     * @param Request                   $request
     * @param JournalCollectorInterface $collector
     * @param Tag                       $tag
     *
     * @return View
     */
    public function showAll(Request $request, JournalCollectorInterface $collector, Tag $tag)
    {
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page)->setTag($tag)
            ->withOpposingAccount()->disableInternalFilter()
            ->withBudgetInformation()->withCategoryInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('tags/show/' . $tag->id . '/all');

        $sum = $journals->sum(
            function (Transaction $transaction) {
                return $transaction->transaction_amount;
            }
        );

        return view('tags.show', compact('tag', 'subTitle', 'subTitleIcon', 'journals', 'sum', 'start', 'end'));

    }

    public function showByDate(Request $request, JournalCollectorInterface $collector, Tag $tag, string $date)
    {
        $range = Preferences::get('viewRange', '1M')->data;

        try {
            $start = new Carbon($date);
            $end   = Navigation::endOfPeriod($start, $range);
        } catch (Exception $e) {
            $start = Navigation::startOfPeriod($this->repository->firstUseDate($tag), $range);
            $end   = Navigation::startOfPeriod($this->repository->lastUseDate($tag), $range);
        }

        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $periods      = $this->getPeriodOverview($tag);

        // use collector:
        $collector->setAllAssetAccounts()
                  ->setLimit($pageSize)->setPage($page)->setTag($tag)->withOpposingAccount()->disableInternalFilter()
                  ->withBudgetInformation()->withCategoryInformation()->setRange($start, $end);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('tags/show/' . $tag->id);

        $sum = $journals->sum(
            function (Transaction $transaction) {
                return $transaction->transaction_amount;
            }
        );

        return view('tags.show', compact('tag', 'periods', 'subTitle', 'subTitleIcon', 'journals', 'sum', 'start', 'end'));
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

        Session::flash('success', strval(trans('firefly.created_tag', ['tag' => e($data['tag'])])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('tags.create.fromStore', true);

            return redirect(route('tags.create'))->withInput();
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

        Session::flash('success', strval(trans('firefly.updated_tag', ['tag' => e($data['tag'])])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('tags.edit.fromUpdate', true);

            return redirect(route('tags.edit', [$tag->id]))->withInput(['return_to_edit' => 1]);
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
            return $cache->get();
        }

        $collection = new Collection;

        // while end larger or equal to start
        while ($end >= $start) {
            $currentEnd = Navigation::endOfPeriod($end, $range);

            // get expenses and what-not in this period and this tag.
            $arr = [
                'date_string' => $end->format('Y-m-d'),
                'date_name'   => Navigation::periodShow($end, $range),
                'date'        => $end,
                'spent'       => $this->repository->spentInperiod($tag, $end, $currentEnd),
                'earned'      => $this->repository->earnedInperiod($tag, $end, $currentEnd),
            ];
            $collection->push($arr);

            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        $cache->store($collection);

        return $collection;

    }
}

<?php
/**
 * TagController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class TagController.
 */
class TagController extends Controller
{
    /** @var TagRepositoryInterface */
    protected $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('hideTags', true);
        $this->redirectUri = route('tags.index');

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(TagRepositoryInterface::class);
                app('view')->share('title', (string)trans('firefly.tags'));
                app('view')->share('mainTitleIcon', 'fa-tags');

                return $next($request);
            }
        );
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = (string)trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('tags.create.fromStore')) {
            $this->rememberPreviousUri('tags.create.uri');
        }
        session()->forget('tags.create.fromStore');

        return view('tags.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Tag $tag)
    {
        $subTitle = (string)trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]);

        // put previous url in session
        $this->rememberPreviousUri('tags.delete.uri');

        return view('tags.delete', compact('tag', 'subTitle'));
    }

    /**
     * @param Tag $tag
     *
     * @return RedirectResponse
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tagName = $tag->tag;
        $this->repository->destroy($tag);

        session()->flash('success', (string)trans('firefly.deleted_tag', ['tag' => $tagName]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('tags.delete.uri'));
    }

    /**
     * Edit a tag.
     *
     * @param Tag $tag
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = (string)trans('firefly.edit_tag', ['tag' => $tag->tag]);
        $subTitleIcon = 'fa-tag';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('tags.edit.fromUpdate')) {
            $this->rememberPreviousUri('tags.edit.uri');
        }
        session()->forget('tags.edit.fromUpdate');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Edit a tag.
     *
     * @param TagRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(TagRepositoryInterface $repository)
    {
        // start with oldest tag
        $oldestTagDate = null === $repository->oldestTag() ? clone session('first') : $repository->oldestTag()->date;
        $newestTagDate = null === $repository->newestTag() ? new Carbon : $repository->newestTag()->date;
        $oldestTagDate->startOfYear();
        $newestTagDate->endOfYear();
        $clouds            = [];
        $clouds['no-date'] = $repository->tagCloud(null);

        while ($newestTagDate > $oldestTagDate) {
            $year          = $newestTagDate->year;
            $clouds[$year] = $repository->tagCloud($year);

            $newestTagDate->subYear();
        }
        $count = $repository->count();

        return view('tags.index', compact('clouds', 'count'));
    }

    /**
     * @param Request     $request
     * @param Tag         $tag
     * @param string|null $moment
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Tag $tag, string $moment = null)
    {
        // default values:
        $moment       = $moment ?? '';
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $range        = app('preferences')->get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;
        $path         = route('tags.show', [$tag->id]);

        // prep for "all" view.
        if ('all' === $moment) {
            $subTitle = (string)trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
            $start    = $this->repository->firstUseDate($tag);
            $end      = new Carbon;
            $path     = route('tags.show', [$tag->id, 'all']);
        }

        // prep for "specific date" view.
        if ('all' !== $moment && \strlen($moment) > 0) {
            $start = new Carbon($moment);
            /** @var Carbon $end */
            $end      = app('navigation')->endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.journals_in_period_for_tag',
                ['tag'   => $tag->tag,
                 'start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat),]
            );
            $periods  = $this->getPeriodOverview($tag);
            $path     = route('tags.show', [$tag->id, $moment]);
        }

        // prep for current period
        if ('' === $moment) {
            /** @var Carbon $start */
            $start = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            /** @var Carbon $end */
            $end      = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
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
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        $sums = $this->repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('tag', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'transactions', 'start', 'end', 'moment'));
    }

    /**
     * @param TagFormRequest $request
     *
     * @return RedirectResponse
     */
    public function store(TagFormRequest $request): RedirectResponse
    {
        $data = $request->collectTagData();
        $this->repository->store($data);

        session()->flash('success', (string)trans('firefly.created_tag', ['tag' => $data['tag']]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('tags.create.uri'));
        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            session()->put('tags.create.fromStore', true);

            $redirect = redirect(route('tags.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;

    }

    /**
     * @param TagFormRequest $request
     * @param Tag            $tag
     *
     * @return RedirectResponse
     */
    public function update(TagFormRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->collectTagData();
        $this->repository->update($tag, $data);

        session()->flash('success', (string)trans('firefly.updated_tag', ['tag' => $data['tag']]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('tags.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('tags.edit.fromUpdate', true);

            $redirect = redirect(route('tags.edit', [$tag->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return $redirect;
    }

    /**
     * @param Tag $tag
     *
     * @return Collection
     */
    private function getPeriodOverview(Tag $tag): Collection
    {
        // get first and last tag date from tag:
        $range = app('preferences')->get('viewRange', '1M')->data;
        /** @var Carbon $end */
        $end   = app('navigation')->endOfX($this->repository->lastUseDate($tag), $range, null);
        $start = $this->repository->firstUseDate($tag);


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
        $currentEnd = clone $end;
        // while end larger or equal to start
        while ($currentEnd >= $start) {
            $currentStart = app('navigation')->startOfPeriod($currentEnd, $range);

            // get expenses and what-not in this period and this tag.
            $arr = [
                'string' => $end->format('Y-m-d'),
                'name'   => app('navigation')->periodShow($currentEnd, $range),
                'date'   => clone $end,
                'spent'  => $this->repository->spentInPeriod($tag, $currentStart, $currentEnd),
                'earned' => $this->repository->earnedInPeriod($tag, $currentStart, $currentEnd),
            ];
            $collection->push($arr);

            /** @var Carbon $currentEnd */
            $currentEnd = clone $currentStart;
            $currentEnd->subDay();
        }
        $cache->store($collection);

        return $collection;
    }
}

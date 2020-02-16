<?php
/**
 * TagController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class TagController.
 */
class TagController extends Controller
{
    use PeriodOverview;

    /** @var TagRepositoryInterface The tag repository. */
    protected $repository;

    /**
     * TagController constructor.
     */
    public function __construct()
    {
        parent::__construct();
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
     *
     */
    public function massDestroy(Request $request)
    {
        $tags = $request->get('tags');
        if (null === $tags || !is_array($tags)) {
            session()->flash('info', (string)trans('firefly.select_tags_to_delete'));

            return redirect(route('tags.index'));
        }
        $count = 0;
        foreach ($tags as $tagId) {
            $tagId = (int)$tagId;
            $tag   = $this->repository->findNull($tagId);
            if (null !== $tag) {
                $this->repository->destroy($tag);
                $count++;
            }
        }
        session()->flash('success', (string)trans('firefly.deleted_x_tags', ['count' => $count]));

        return redirect(route('tags.index'));
    }

    /**
     * Create a new tag.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        $subTitle     = (string)trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';

        // location info:
        $hasOldInput = null !== $request->old('_token');
        $locations   = [
            'location' => [
                'latitude'     => $hasOldInput ? old('location_latitude') : config('firefly.default_location.latitude'),
                'longitude'    => $hasOldInput ? old('location_longitude') : config('firefly.default_location.longitude'),
                'zoom_level'   => $hasOldInput ? old('location_zoom_level') : config('firefly.default_location.zoom_level'),
                'has_location' => $hasOldInput ? 'true' === old('location_has_location') : false,
            ],
        ];

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('tags.create.fromStore')) {
            $this->rememberPreviousUri('tags.create.uri');
        }
        session()->forget('tags.create.fromStore');

        return view('tags.create', compact('subTitle', 'subTitleIcon', 'locations'));
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
     * Destroy a tag.
     *
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

        $location       = $this->repository->getLocation($tag);
        $latitude       = $location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude      = $location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel      = $location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $hasLocation    = null !== $location;
        $locations      = [
            'location' => [
                'latitude'     => old('location_latitude') ?? $latitude,
                'longitude'    => old('location_longitude') ?? $longitude,
                'zoom_level'   => old('location_zoom_level') ?? $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('tags.edit.fromUpdate')) {
            $this->rememberPreviousUri('tags.edit.uri');
        }
        session()->forget('tags.edit.fromUpdate');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon','locations'));
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
        $tags            = [];
        $tags['no-date'] = $repository->getTagsInYear(null);

        while ($newestTagDate > $oldestTagDate) {
            $year          = $newestTagDate->year;
            $tags[$year] = $repository->getTagsInYear($year);
            $newestTagDate->subYear();
        }
        $count = $repository->count();

        return view('tags.index', compact('tags', 'count'));
    }

    /**
     * Show a single tag.
     *
     * @param Request $request
     * @param Tag $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */
    public function show(Request $request, Tag $tag, Carbon $start = null, Carbon $end = null)
    {
        // default values:
        $subTitleIcon = 'fa-tag';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $start        = $start ?? session('start');
        $end          = $end ?? session('end');
        $location     = $this->repository->getLocation($tag);
        $subTitle     = trans(
            'firefly.journals_in_period_for_tag', ['tag' => $tag->tag, 'start' => $start->formatLocalized($this->monthAndDayFormat),
                                                   'end' => $end->formatLocalized($this->monthAndDayFormat),]
        );

        $startPeriod = $this->repository->firstUseDate($tag);
        $startPeriod  = $startPeriod ?? new Carbon;
        $endPeriod    = clone $end;
        $periods      = $this->getTagPeriodOverview($tag, $startPeriod, $endPeriod);
        $path         = route('tags.show', [$tag->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withAccountInformation()
                  ->setTag($tag)->withBudgetInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);
        $sums = $this->repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('tag', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'groups', 'start', 'end', 'location'));
    }

    /**
     * Show a single tag over all time.
     *
     * @param Request $request
     * @param Tag $tag
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */
    public function showAll(Request $request, Tag $tag)
    {
        // default values:
        $subTitleIcon = 'fa-tag';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $periods      = [];
        $subTitle     = (string)trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
        $start        = $this->repository->firstUseDate($tag) ?? new Carbon;
        $end          = new Carbon;
        $path         = route('tags.show', [$tag->id, 'all']);
        $location     = $this->repository->getLocation($tag);
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withAccountInformation()
                  ->setTag($tag)->withBudgetInformation()->withCategoryInformation();
        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);
        $sums = $this->repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('tag', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'groups', 'start', 'end', 'location'));
    }

    /**
     * Store a tag.
     *
     * @param TagFormRequest $request
     *
     * @return RedirectResponse
     */
    public function store(TagFormRequest $request): RedirectResponse
    {
        $data = $request->collectTagData();
        Log::debug('Data from request', $data);

        $result = $this->repository->store($data);
        Log::debug('Data after storage', $result->toArray());

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
     * Update a tag.
     *
     * @param TagFormRequest $request
     * @param Tag $tag
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


}

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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Models\Location;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class TagController.
 */
class TagController extends Controller
{
    use PeriodOverview;

    protected TagRepositoryInterface  $repository;
    private AttachmentHelperInterface $attachmentsHelper;

    /**
     * TagController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->redirectUrl = route('tags.index');

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.tags'));
                app('view')->share('mainTitleIcon', 'fa-tag');

                $this->attachmentsHelper = app(AttachmentHelperInterface::class);
                $this->repository        = app(TagRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new tag.
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $subTitle     = (string) trans('firefly.new_tag');
        $subTitleIcon = 'fa-tag';

        // location info:
        $hasOldInput  = null !== $request->old('_token');
        $locations    = [
            'location' => [
                'latitude'     => $hasOldInput ? old('location_latitude') : config('firefly.default_location.latitude'),
                'longitude'    => $hasOldInput ? old('location_longitude') : config('firefly.default_location.longitude'),
                'zoom_level'   => $hasOldInput ? old('location_zoom_level') : config('firefly.default_location.zoom_level'),
                'has_location' => $hasOldInput ? 'true' === old('location_has_location') : false,
            ],
        ];

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('tags.create.fromStore')) {
            $this->rememberPreviousUrl('tags.create.url');
        }
        session()->forget('tags.create.fromStore');

        return view('tags.create', compact('subTitle', 'subTitleIcon', 'locations'));
    }

    /**
     * Delete a tag.
     *
     * @return Factory|View
     */
    public function delete(Tag $tag)
    {
        $subTitle = (string) trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]);

        // put previous url in session
        $this->rememberPreviousUrl('tags.delete.url');

        return view('tags.delete', compact('tag', 'subTitle'));
    }

    /**
     * Edit a tag.
     *
     * @return Factory|View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = (string) trans('firefly.edit_tag', ['tag' => $tag->tag]);
        $subTitleIcon = 'fa-tag';

        $location     = $this->repository->getLocation($tag);
        $latitude     = $location instanceof Location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude    = $location instanceof Location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel    = $location instanceof Location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $hasLocation  = $location instanceof Location;
        $locations    = [
            'location' => [
                'latitude'     => old('location_latitude') ?? $latitude,
                'longitude'    => old('location_longitude') ?? $longitude,
                'zoom_level'   => old('location_zoom_level') ?? $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('tags.edit.fromUpdate')) {
            $this->rememberPreviousUrl('tags.edit.url');
        }
        session()->forget('tags.edit.fromUpdate');

        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon', 'locations'));
    }

    /**
     * Edit a tag.
     *
     * @return Factory|View
     */
    public function index(TagRepositoryInterface $repository)
    {
        // start with oldest tag
        $first           = session('first', today()) ?? today();
        $oldestTagDate   = $repository->oldestTag() instanceof Tag ? $repository->oldestTag()->date : clone $first;
        $newestTagDate   = $repository->newestTag() instanceof Tag ? $repository->newestTag()->date : today();
        $oldestTagDate->startOfYear();
        $newestTagDate->endOfYear();
        $tags            = [];
        $tags['no-date'] = $repository->getTagsInYear(null);

        while ($newestTagDate > $oldestTagDate) {
            $year        = $newestTagDate->year;
            $tags[$year] = $repository->getTagsInYear($year);
            $newestTagDate->subYear();
        }
        $count           = $repository->count();

        return view('tags.index', compact('tags', 'count'));
    }

    public function massDestroy(Request $request): RedirectResponse
    {
        $tags  = $request->get('tags');
        if (null === $tags || !is_array($tags)) {
            session()->flash('info', (string) trans('firefly.select_tags_to_delete'));

            return redirect(route('tags.index'));
        }
        $count = 0;
        foreach ($tags as $tagId) {
            $tagId = (int) $tagId;
            $tag   = $this->repository->find($tagId);
            if ($tag instanceof Tag) {
                $this->repository->destroy($tag);
                ++$count;
            }
        }
        session()->flash('success', trans_choice('firefly.deleted_x_tags', $count));

        return redirect(route('tags.index'));
    }

    /**
     * Destroy a tag.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tagName = $tag->tag;
        $this->repository->destroy($tag);

        session()->flash('success', (string) trans('firefly.deleted_tag', ['tag' => $tagName]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('tags.delete.url'));
    }

    /**
     * Show a single tag.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function show(Request $request, Tag $tag, ?Carbon $start = null, ?Carbon $end = null)
    {
        // default values:
        $subTitleIcon = 'fa-tag';
        $page         = (int) $request->get('page');
        $pageSize     = (int) app('preferences')->get('listPageSize', 50)->data;
        $start       ??= session('start');
        $end         ??= session('end');
        $location     = $this->repository->getLocation($tag);
        $attachments  = $this->repository->getAttachments($tag);
        $subTitle     = trans(
            'firefly.journals_in_period_for_tag',
            [
                'tag'   => $tag->tag,
                'start' => $start->isoFormat($this->monthAndDayFormat),
                'end'   => $end->isoFormat($this->monthAndDayFormat),
            ]
        );

        $startPeriod  = $this->repository->firstUseDate($tag);
        $startPeriod ??= today(config('app.timezone'));
        $endPeriod    = clone $end;
        $periods      = $this->getTagPeriodOverview($tag, $startPeriod, $endPeriod);
        $path         = route('tags.show', [$tag->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);

        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withAccountInformation()
            ->setTag($tag)->withBudgetInformation()->withCategoryInformation()
        ;
        $groups       = $collector->getPaginatedGroups();
        $groups->setPath($path);
        $sums         = $this->repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('tag', 'attachments', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'groups', 'start', 'end', 'location'));
    }

    /**
     * Show a single tag over all time.
     *
     * @return Factory|View
     */
    public function showAll(Request $request, Tag $tag)
    {
        // default values:
        $subTitleIcon = 'fa-tag';
        $page         = (int) $request->get('page');
        $pageSize     = (int) app('preferences')->get('listPageSize', 50)->data;
        $periods      = [];
        $subTitle     = (string) trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
        $start        = $this->repository->firstUseDate($tag) ?? today(config('app.timezone'));
        $end          = $this->repository->lastUseDate($tag) ?? today(config('app.timezone'));
        $attachments  = $this->repository->getAttachments($tag);
        $path         = route('tags.show', [$tag->id, 'all']);
        $location     = $this->repository->getLocation($tag);

        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withAccountInformation()
            ->setTag($tag)->withBudgetInformation()->withCategoryInformation()
        ;
        $groups       = $collector->getPaginatedGroups();
        $groups->setPath($path);
        $sums         = $this->repository->sumsOfTag($tag, $start, $end);

        return view('tags.show', compact('tag', 'attachments', 'sums', 'periods', 'subTitle', 'subTitleIcon', 'groups', 'start', 'end', 'location'));
    }

    /**
     * Store a tag.
     */
    public function store(TagFormRequest $request): RedirectResponse
    {
        $data     = $request->collectTagData();
        app('log')->debug('Data from request', $data);

        $result   = $this->repository->store($data);
        app('log')->debug('Data after storage', $result->toArray());

        session()->flash('success', (string) trans('firefly.created_tag', ['tag' => $data['tag']]));
        app('preferences')->mark();

        // store attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachmentsHelper->saveAttachmentsForModel($result, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachmentsHelper->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachmentsHelper->getMessages()->get('attachments'));
        }
        if (count($this->attachmentsHelper->getErrors()->get('attachments')) > 0) {
            $request->session()->flash('error', $this->attachmentsHelper->getErrors()->get('attachments'));
        }
        $redirect = redirect($this->getPreviousUrl('tags.create.url'));
        if (1 === (int) $request->get('create_another')) {
            session()->put('tags.create.fromStore', true);

            $redirect = redirect(route('tags.create'))->withInput();
        }

        return $redirect;
    }

    /**
     * Update a tag.
     */
    public function update(TagFormRequest $request, Tag $tag): RedirectResponse
    {
        $data     = $request->collectTagData();
        $tag      = $this->repository->update($tag, $data);

        session()->flash('success', (string) trans('firefly.updated_tag', ['tag' => $data['tag']]));
        app('preferences')->mark();

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachmentsHelper->saveAttachmentsForModel($tag, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachmentsHelper->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachmentsHelper->getMessages()->get('attachments'));
        }
        if (count($this->attachmentsHelper->getErrors()->get('attachments')) > 0) {
            $request->session()->flash('error', $this->attachmentsHelper->getErrors()->get('attachments'));
        }
        $redirect = redirect($this->getPreviousUrl('tags.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            session()->put('tags.edit.fromUpdate', true);

            $redirect = redirect(route('tags.edit', [$tag->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return $redirect;
    }
}

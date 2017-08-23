<?php
/**
 * LinkController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\LinkTypeFormRequest;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;
use View;

/**
 * Class LinkController
 *
 * @package FireflyIII\Http\Controllers\Admin
 */
class LinkController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('title', strval(trans('firefly.administration')));
                View::share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = trans('firefly.create_new_link_type');
        $subTitleIcon = 'fa-link';

        // put previous url in session if not redirect from store (not "create another").
        if (session('link_types.create.fromStore') !== true) {
            $this->rememberPreviousUri('link_types.create.uri');
        }

        return view('admin.link.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * @param LinkType $linkType
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(Request $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', strval(trans('firefly.cannot_edit_link_type', ['name' => $linkType->name])));

            return redirect(route('admin.links.index'));
        }

        $subTitle   = trans('firefly.delete_link_type', ['name' => $linkType->name]);
        $otherTypes = $repository->get();
        $count      = $repository->countJournals($linkType);
        $moveTo     = [];
        $moveTo[0]  = trans('firefly.do_not_save_connection');
        /** @var LinkType $otherType */
        foreach ($otherTypes as $otherType) {
            if ($otherType->id !== $linkType->id) {
                $moveTo[$otherType->id] = sprintf('%s (%s / %s)', $otherType->name, $otherType->inward, $otherType->outward);
            }
        }
        // put previous url in session
        $this->rememberPreviousUri('link_types.delete.uri');

        return view('admin.link.delete', compact('linkType', 'subTitle', 'moveTo', 'count'));
    }

    /**
     * @param Request                     $request
     * @param LinkTypeRepositoryInterface $repository
     * @param LinkType                    $linkType
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        $name   = $linkType->name;
        $moveTo = $repository->find(intval($request->get('move_link_type_before_delete')));
        $repository->destroy($linkType, $moveTo);

        $request->session()->flash('success', strval(trans('firefly.deleted_link_type', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('link_types.delete.uri'));
    }

    /**
     * @param Request  $request
     * @param LinkType $linkType
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', strval(trans('firefly.cannot_edit_link_type', ['name' => $linkType->name])));

            return redirect(route('admin.links.index'));
        }
        $subTitle     = trans('firefly.edit_link_type', ['name' => $linkType->name]);
        $subTitleIcon = 'fa-link';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('link_types.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('link_types.edit.uri');
        }
        $request->session()->forget('link_types.edit.fromUpdate');

        return view('admin.link.edit', compact('subTitle', 'subTitleIcon', 'linkType'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(LinkTypeRepositoryInterface $repository)
    {
        $subTitle     = trans('firefly.journal_link_configuration');
        $subTitleIcon = 'fa-link';
        $linkTypes    = $repository->get();
        $linkTypes->each(
            function (LinkType $linkType) use ($repository) {
                $linkType->journalCount = $repository->countJournals($linkType);
            }
        );

        return view('admin.link.index', compact('subTitle', 'subTitleIcon', 'linkTypes'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(LinkType $linkType)
    {
        $subTitle     = trans('firefly.overview_for_link', ['name' => $linkType->name]);
        $subTitleIcon = 'fa-link';
        $links        = $linkType->transactionJournalLinks()->get();

        return view('admin.link.show', compact('subTitle', 'subTitleIcon', 'linkType', 'links'));
    }

    /**
     * @param LinkTypeFormRequest         $request
     * @param LinkTypeRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(LinkTypeFormRequest $request, LinkTypeRepositoryInterface $repository)
    {
        $data     = [
            'name'    => $request->string('name'),
            'inward'  => $request->string('inward'),
            'outward' => $request->string('outward'),
        ];
        $linkType = $repository->store($data);
        $request->session()->flash('success', strval(trans('firefly.stored_new_link_type', ['name' => $linkType->name])));

        if (intval($request->get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('link_types.create.fromStore', true);

            return redirect(route('link_types.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('link_types.create.uri'));
    }

    public function update(LinkTypeFormRequest $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', strval(trans('firefly.cannot_edit_link_type', ['name' => $linkType->name])));

            return redirect(route('admin.links.index'));
        }

        $data = [
            'name'    => $request->string('name'),
            'inward'  => $request->string('inward'),
            'outward' => $request->string('outward'),
        ];
        $repository->update($linkType, $data);

        $request->session()->flash('success', strval(trans('firefly.updated_link_type', ['name' => $linkType->name])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('link_types.edit.fromUpdate', true);

            return redirect(route('admin.links.edit', [$linkType->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('link_types.edit.uri'));
    }

}
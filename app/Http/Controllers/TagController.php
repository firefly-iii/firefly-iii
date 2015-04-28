<?php

namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests\TagFormRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Input;
use Preferences;
use Response;
use Session;
use View;
use URL;
use Redirect;

/**
 * Class TagController
 *
 * @package FireflyIII\Http\Controllers
 */
class TagController extends Controller
{
    /**
     *
     */
    public function __construct()
    {

        View::share('title', 'Tags');
        View::share('mainTitleIcon', 'fa-tags');
        $tagOptions = [
            'nothing'        => 'Just a regular tag.',
            'balancingAct'   => 'The tag takes at most two transactions; an expense and a transfer. They\'ll balance each other out.',
            'advancePayment' => 'The tag accepts one expense and any number of deposits aimed to repay the original expense.',
        ];
        View::share('tagOptions', $tagOptions);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = 'New tag';
        $subTitleIcon = 'fa-tag';

        $preFilled = [
            'tagMode' => 'nothing'
        ];
        if (!Input::old('tagMode')) {
            Session::flash('preFilled', $preFilled);
        }
        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('tags.create.fromStore') !== true) {
            Session::put('tags.create.url', URL::previous());
        }
        Session::forget('tags.create.fromStore');

        return view('tags.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * @param Tag $tag
     *
     * @return View
     */
    public function edit(Tag $tag)
    {
        $subTitle     = 'Edit tag "' . e($tag->tag) . '"';
        $subTitleIcon = 'fa-tag';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('tags.edit.fromUpdate') !== true) {
            Session::put('tags.edit.url', URL::previous());
        }
        Session::forget('tags.edit.fromUpdate');


        return view('tags.edit', compact('tag', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param $state
     */
    public function hideTagHelp($state)
    {

        $state = $state == 'true' ? true : false;
        Preferences::set('hideTagHelp', $state);

        return Response::json(true);
    }

    /**
     *
     */
    public function index()
    {
        /** @var Preference $helpHiddenPref */
        $helpHiddenPref = Preferences::get('hideTagHelp', false);
        $title          = 'Tags';
        $mainTitleIcon  = 'fa-tags';
        $helpHidden     = $helpHiddenPref->data;
        $tags           = Auth::user()->tags()->get();

        return view('tags.index', compact('title', 'mainTitleIcon', 'helpHidden', 'tags'));
    }

    /**
     * @param Tag $tag
     *
     * @return \Illuminate\View\View
     */
    public function show(Tag $tag)
    {
        $subTitle     = $tag->tag;
        $subTitleIcon = 'fa-tag';

        return view('tags.show', compact('tag', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param TagFormRequest $request
     */
    public function store(TagFormRequest $request, TagRepositoryInterface $repository)
    {
        if (Input::get('setTag') == 'true') {
            $latitude  = strlen($request->get('latitude')) > 0 ? $request->get('latitude') : null;
            $longitude = strlen($request->get('longitude')) > 0 ? $request->get('longitude') : null;
            $zoomLevel = strlen($request->get('zoomLevel')) > 0 ? $request->get('zoomLevel') : null;
        } else {
            $latitude  = null;
            $longitude = null;
            $zoomLevel = null;
        }

        $data = [
            'tag'         => $request->get('tag'),
            'date'        => strlen($request->get('date')) > 0 ? new Carbon($request->get('date')) : null,
            'description' => strlen($request->get('description')) > 0 ? $request->get('description') : '',
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
            'tagMode'     => $request->get('tagMode'),
        ];
        $repository->store($data);

        Session::flash('success','The tag has been created!');

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('tags.create.fromStore', true);

            return Redirect::route('tags.create')->withInput();
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('tags.create.url'));

    }

    /**
     * @param Tag $tag
     */
    public function update(Tag $tag, TagFormRequest $request, TagRepositoryInterface $repository) {
        if (Input::get('setTag') == 'true') {
            $latitude  = strlen($request->get('latitude')) > 0 ? $request->get('latitude') : null;
            $longitude = strlen($request->get('longitude')) > 0 ? $request->get('longitude') : null;
            $zoomLevel = strlen($request->get('zoomLevel')) > 0 ? $request->get('zoomLevel') : null;
        } else {
            $latitude  = null;
            $longitude = null;
            $zoomLevel = null;
        }

        $data = [
            'tag'         => $request->get('tag'),
            'date'        => strlen($request->get('date')) > 0 ? new Carbon($request->get('date')) : null,
            'description' => strlen($request->get('description')) > 0 ? $request->get('description') : '',
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'zoomLevel'   => $zoomLevel,
            'tagMode'     => $request->get('tagMode'),
        ];

        $repository->update($tag, $data);

        Session::flash('success', 'Tag "' . e($data['tag']) . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('tags.edit.fromUpdate', true);

            return Redirect::route('tags.edit', $tag->id)->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('tags.edit.url'));
    }
}
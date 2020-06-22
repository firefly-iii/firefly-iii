<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\ObjectGroup;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ObjectGroupFormRequest;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private ObjectGroupRepositoryInterface $repository;

    /**
     * PiggyBankController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

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
     * Edit an object group.
     *
     * @param ObjectGroup $objectGroup
     */
    public function edit(ObjectGroup $objectGroup)
    {
        $subTitle     = (string) trans('firefly.edit_object_group', ['title' => $objectGroup->title]);
        $subTitleIcon = 'fa-pencil';
        $targetDate   = null;
        $startDate    = null;

        if (true !== session('object-groups.edit.fromUpdate')) {
            $this->rememberPreviousUri('object-groups.edit.uri');
        }
        session()->forget('object-groups.edit.fromUpdate');

        return view('object-groups.edit', compact('subTitle', 'subTitleIcon', 'objectGroup'));
    }


    /**
     * Update a piggy bank.
     *
     * @param ObjectGroupFormRequest $request
     * @param ObjectGroup $objectGroup
     */
    public function update(ObjectGroupFormRequest $request, ObjectGroup $objectGroup)
    {
        $data      = $request->getObjectGroupData();
        $piggyBank = $this->repository->update($objectGroup, $data);

        session()->flash('success', (string) trans('firefly.updated_object_group', ['title' => $objectGroup->title]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('object-groups.edit.uri'));

        if (1 === (int) $request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('object-groups.edit.fromUpdate', true);

            $redirect = redirect(route('object-groups.edit', [$piggyBank->id]));
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}

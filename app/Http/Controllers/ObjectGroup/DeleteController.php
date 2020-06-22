<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\ObjectGroup;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use Illuminate\Http\RedirectResponse;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
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
     * Delete a piggy bank.
     *
     * @param ObjectGroup $objectGroup
     */
    public function delete(ObjectGroup $objectGroup)
    {
        $subTitle   = (string) trans('firefly.delete_object_group', ['title' => $objectGroup->title]);
        $piggyBanks = $objectGroup->piggyBanks()->count();

        // put previous url in session
        $this->rememberPreviousUri('object-groups.delete.uri');

        return view('object-groups.delete', compact('objectGroup', 'subTitle', 'piggyBanks'));
    }

    /**
     * Destroy the piggy bank.
     *
     * @param ObjectGroup $objectGroup
     */
    public function destroy(ObjectGroup $objectGroup): RedirectResponse
    {
        session()->flash('success', (string) trans('firefly.deleted_object_group', ['title' => $objectGroup->title]));
        app('preferences')->mark();
        $this->repository->destroy($objectGroup);

        return redirect($this->getPreviousUri('object-groups.delete.uri'));
    }

}

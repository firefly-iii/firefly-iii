<?php
/**
 * CostCenterController.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests\CostCenterFormRequest;
use FireflyIII\Models\CostCenter;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CostCenterController.
 */
class CostCenterController extends Controller
{
    /** @var CostCenterRepositoryInterface The cost center repository */
    private $repository;

    /**
     * CostCenterController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.cost_centers'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository = app(CostCenterRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create cost center.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */   
    public function create(Request $request)
    {
        if (true !== session('cost-centers.create.fromStore')) {
            $this->rememberPreviousUri('cost-centers.create.uri');
        }
        $request->session()->forget('cost-centers.create.fromStore');
        $subTitle = (string)trans('firefly.create_new_cost_center');

        return view('cost-centers.create', compact('subTitle'));
    }

    /**
     * Delete a cost center.
     *
     * @param CostCenter $costCenter
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(CostCenter $costCenter)
    {
        $subTitle = (string)trans('firefly.delete_cost_center', ['name' => $costCenter->name]);

        // put previous url in session
        $this->rememberPreviousUri('cost-centers.delete.uri');

        return view('cost-centers.delete', compact('costCenter', 'subTitle'));
    }

    /**
     * Destroy a cost center.
     *
     * @param Request  $request
     * @param CostCenter $costCenter
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, CostCenter $costCenter)
    {
        $name = $costCenter->name;
        $this->repository->destroy($costCenter);

        $request->session()->flash('success', (string)trans('firefly.deleted_cost_center', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('cost-centers.delete.uri'));
    }

    /**
     * Edit a costCenter.
     *
     * @param Request  $request
     * @param CostCenter $costCenter
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, CostCenter $costCenter)
    {
        //$costCenter = $this->repository->getByIds([$request->route()->parameter('costCenter')])[0];

        $subTitle = (string)trans('firefly.edit_cost_center', ['name' => $costCenter->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('cost-centers.edit.fromUpdate')) {
            $this->rememberPreviousUri('cost-centers.edit.uri');
        }
        $request->session()->forget('cost-centers.edit.fromUpdate');

        return view('cost-centers.edit', compact('costCenter', 'subTitle'));
    }

    /**
     * Show all cost center.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->repository->getCostCenters();
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);

        $collection->each(
            function (CostCenter $costCenter) {
                $costCenter->lastActivity = $this->repository->lastUseDate($costCenter, new Collection);
            }
        );

        // paginate cost center
        $costCenters = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $costCenters->setPath(route('cost-centers.index'));

        return view('cost-centers.index', compact('costCenters'));
    }


    /**
     * Store new cost center.
     *
     * @param CostCenterFormRequest         $request
     * @param CostCenterRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(CostCenterFormRequest $request, CostCenterRepositoryInterface $repository)
    {
        $data     = $request->getCostCenterData();
        $costCenter = $repository->store($data);

        $request->session()->flash('success', (string)trans('firefly.stored_cost_center', ['name' => $costCenter->name]));
        app('preferences')->mark();

        $redirect = redirect(route('cost-centers.index'));
        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('cost-centers.create.fromStore', true);

            $redirect = redirect(route('cost-centers.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }


    /**
     * Update cost center.
     *
     * @param CostCenterFormRequest         $request
     * @param CostCenterRepositoryInterface $repository
     * @param CostCenter                    $costCenter
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(CostCenterFormRequest $request, CostCenterRepositoryInterface $repository, CostCenter $costCenter)
    {
        $data = $request->getCostCenterData();
        $repository->update($costCenter, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_cost_center', ['name' => $costCenter->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('cost-centers.edit.uri'));

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('cost-centers.edit.fromUpdate', true);

            $redirect = redirect(route('cost-centers.edit', [$costCenter->id]));
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }


}

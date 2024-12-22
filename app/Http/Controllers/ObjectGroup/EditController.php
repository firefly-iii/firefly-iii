<?php

/**
 * EditController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\ObjectGroup;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ObjectGroupFormRequest;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private ObjectGroupRepositoryInterface $repository;

    /**
     * PiggyBankController constructor.
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
     * @return Factory|View
     */
    public function edit(ObjectGroup $objectGroup)
    {
        $subTitle     = (string) trans('firefly.edit_object_group', ['title' => $objectGroup->title]);
        $subTitleIcon = 'fa-pencil';

        if (true !== session('object-groups.edit.fromUpdate')) {
            $this->rememberPreviousUrl('object-groups.edit.url');
        }
        session()->forget('object-groups.edit.fromUpdate');

        return view('object-groups.edit', compact('subTitle', 'subTitleIcon', 'objectGroup'));
    }

    /**
     * Update a piggy bank.
     *
     * @return Application|Redirector|RedirectResponse
     */
    public function update(ObjectGroupFormRequest $request, ObjectGroup $objectGroup)
    {
        $data      = $request->getObjectGroupData();
        $piggyBank = $this->repository->update($objectGroup, $data);

        session()->flash('success', (string) trans('firefly.updated_object_group', ['title' => $objectGroup->title]));
        app('preferences')->mark();

        $redirect  = redirect($this->getPreviousUrl('object-groups.edit.url'));

        if (1 === (int) $request->get('return_to_edit')) {
            session()->put('object-groups.edit.fromUpdate', true);

            $redirect = redirect(route('object-groups.edit', [$piggyBank->id]));
        }

        return $redirect;
    }
}

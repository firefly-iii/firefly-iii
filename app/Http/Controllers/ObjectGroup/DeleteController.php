<?php

/**
 * DeleteController.php
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
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
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
     * Delete a piggy bank.
     *
     * @return Factory|View
     */
    public function delete(ObjectGroup $objectGroup)
    {
        $subTitle   = (string) trans('firefly.delete_object_group', ['title' => $objectGroup->title]);
        $piggyBanks = $objectGroup->piggyBanks()->count();

        // put previous url in session
        $this->rememberPreviousUrl('object-groups.delete.url');

        return view('object-groups.delete', compact('objectGroup', 'subTitle', 'piggyBanks'));
    }

    /**
     * Destroy the piggy bank.
     */
    public function destroy(ObjectGroup $objectGroup): RedirectResponse
    {
        session()->flash('success', (string) trans('firefly.deleted_object_group', ['title' => $objectGroup->title]));
        app('preferences')->mark();
        $this->repository->destroy($objectGroup);

        return redirect($this->getPreviousUrl('object-groups.delete.url'));
    }
}

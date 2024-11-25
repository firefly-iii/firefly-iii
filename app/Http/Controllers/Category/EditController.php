<?php

/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Category;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\CategoryFormRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface   $attachments;
    private CategoryRepositoryInterface $repository;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bookmark');
                $this->repository  = app(CategoryRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a category.
     *
     * @return Factory|View
     */
    public function edit(Request $request, Category $category)
    {
        $subTitle  = (string)trans('firefly.edit_category', ['name' => $category->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('categories.edit.fromUpdate')) {
            $this->rememberPreviousUrl('categories.edit.url');
        }
        $request->session()->forget('categories.edit.fromUpdate');

        $preFilled = [
            'notes' => $request->old('notes') ?? $this->repository->getNoteText($category),
        ];

        return view('categories.edit', compact('category', 'subTitle', 'preFilled'));
    }

    /**
     * Update category.
     *
     * @return Redirector|RedirectResponse
     */
    public function update(CategoryFormRequest $request, Category $category)
    {
        $data     = $request->getCategoryData();
        $this->repository->update($category, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_category', ['name' => $category->name]));
        app('preferences')->mark();

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($category, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        $redirect = redirect($this->getPreviousUrl('categories.edit.url'));

        if (1 === (int)$request->get('return_to_edit')) {
            $request->session()->put('categories.edit.fromUpdate', true);

            $redirect = redirect(route('categories.edit', [$category->id]));
        }

        return $redirect;
    }
}

<?php

/**
 * CreateController.php
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

namespace FireflyIII\Http\Controllers\PiggyBank;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\PiggyBankStoreRequest;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private AttachmentHelperInterface    $attachments;
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-bullseye');

                $this->attachments = app(AttachmentHelperInterface::class);
                $this->piggyRepos  = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a piggy bank.
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $subTitle     = (string) trans('firefly.new_piggy_bank');
        $subTitleIcon = 'fa-plus';
        $hasOldInput  = null !== $request->old('_token');
        $preFilled    = $request->old();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('piggy-banks.create.fromStore')) {
            $this->rememberPreviousUrl('piggy-banks.create.url');
        }
        session()->forget('piggy-banks.create.fromStore');

        return view('piggy-banks.create', compact('subTitle', 'subTitleIcon', 'preFilled'));
    }

    /**
     * Store a new piggy bank.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function store(PiggyBankStoreRequest $request)
    {
        $data      = $request->getPiggyBankData();

        if (null === $data['start_date']) {
            $data['start_date'] = today(config('app.timezone'));
        }
        $piggyBank = $this->piggyRepos->store($data);

        session()->flash('success', (string) trans('firefly.stored_piggy_bank', ['name' => $piggyBank->name]));
        session()->flash('success_url', route('piggy-banks.show', [$piggyBank->id]));
        app('preferences')->mark();

        // store attachment(s):
        /** @var null|array $files */
        $files     = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($piggyBank, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        $redirect  = redirect($this->getPreviousUrl('piggy-banks.create.url'));

        if (1 === (int) $request->get('create_another')) {
            session()->put('piggy-banks.create.fromStore', true);

            $redirect = redirect(route('piggy-banks.create'))->withInput();
        }

        return $redirect;
    }
}

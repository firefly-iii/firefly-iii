<?php

/**
 * LinkController.php
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

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalLinkRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class LinkController.
 */
class LinkController extends Controller
{
    private JournalRepositoryInterface $journalRepository;
    private LinkTypeRepositoryInterface $repository;

    /**
     * LinkController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // some useful repositories:
        $this->middleware(function ($request, $next) {
            app('view')->share('title', (string) trans('firefly.transactions'));
            app('view')->share('mainTitleIcon', 'fa-exchange');

            $this->journalRepository = app(JournalRepositoryInterface::class);
            $this->repository = app(LinkTypeRepositoryInterface::class);

            return $next($request);
        });
    }

    /**
     * Delete a link.
     *
     * @return Factory|View
     */
    public function delete(TransactionJournalLink $link): Factory|\Illuminate\Contracts\View\View
    {
        $subTitleIcon = 'fa-link';
        $subTitle     = (string) trans('breadcrumbs.delete_journal_link');
        $this->rememberPreviousUrl('journal_links.delete.url');

        return view('transactions.links.delete', ['link'         => $link, 'subTitle'     => $subTitle, 'subTitleIcon' => $subTitleIcon]);
    }

    /**
     * Actually destroy it.
     */
    public function destroy(TransactionJournalLink $link): Redirector|RedirectResponse
    {
        $this->repository->destroyLink($link);

        session()->flash('success', (string) trans('firefly.deleted_link'));
        Preferences::mark();

        return redirect((string) session('journal_links.delete.url'));
    }

    /**
     * @return Factory|View
     */
    public function modal(TransactionJournal $journal): Factory|\Illuminate\Contracts\View\View
    {
        $linkTypes = $this->repository->get();

        return view('transactions.links.modal', ['journal'   => $journal, 'linkTypes' => $linkTypes]);
    }

    /**
     * Store a new link.
     */
    public function store(JournalLinkRequest $request, TransactionJournal $journal): Redirector|RedirectResponse
    {
        $linkInfo = $request->getLinkInfo();

        Log::debug('We are here (store)');
        $other = $this->journalRepository->find($linkInfo['transaction_journal_id']);
        if (!$other instanceof TransactionJournal) {
            session()->flash('error', (string) trans('firefly.invalid_link_selection'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }

        $alreadyLinked = $this->repository->findLink($journal, $other);

        if ($other->id === $journal->id) {
            session()->flash('error', (string) trans('firefly.journals_link_to_self'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }

        if ($alreadyLinked) {
            session()->flash('error', (string) trans('firefly.journals_error_linked'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }
        Log::debug(sprintf('Journal is %d, opposing is %d', $journal->id, $other->id));
        $this->repository->storeLink($linkInfo, $other, $journal);
        session()->flash('success', (string) trans('firefly.journals_linked'));

        return redirect(route('transactions.show', [$journal->transaction_group_id]));
    }

    /**
     * Switch link from A <> B to B <> A.
     */
    public function switchLink(Request $request): Redirector|RedirectResponse
    {
        $linkId = (int) $request->get('id');
        $this->repository->switchLinkById($linkId);

        return redirect(Steam::getSafePreviousUrl());
    }
}

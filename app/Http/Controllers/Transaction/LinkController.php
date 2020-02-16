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
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Log;
use URL;

/**
 * Class LinkController.
 */
class LinkController extends Controller
{
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $journalRepository;
    /** @var LinkTypeRepositoryInterface Link repository. */
    private $repository;

    /**
     * LinkController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                $this->journalRepository = app(JournalRepositoryInterface::class);
                $this->repository        = app(LinkTypeRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionJournal $journal
     * @return Factory|View
     */
    public function modal(TransactionJournal $journal)
    {
        $linkTypes = $this->repository->get();

        return view('transactions.links.modal', compact('journal', 'linkTypes'));
    }

    /**
     * Delete a link.
     *
     * @param TransactionJournalLink $link
     *
     * @return Factory|View
     */
    public function delete(TransactionJournalLink $link)
    {
        $subTitleIcon = 'fa-link';
        $subTitle     = (string)trans('breadcrumbs.delete_journal_link');
        $this->rememberPreviousUri('journal_links.delete.uri');

        return view('transactions.links.delete', compact('link', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Actually destroy it.
     *
     * @param TransactionJournalLink $link
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(TransactionJournalLink $link)
    {
        $this->repository->destroyLink($link);

        session()->flash('success', (string)trans('firefly.deleted_link'));
        app('preferences')->mark();

        return redirect((string)session('journal_links.delete.uri'));
    }

    /**
     * Store a new link.
     *
     * @param JournalLinkRequest $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(JournalLinkRequest $request, TransactionJournal $journal)
    {
        $linkInfo = $request->getLinkInfo();

        Log::debug('We are here (store)');
        $other    = $this->journalRepository->findNull($linkInfo['transaction_journal_id']);
        if (null === $other) {
            session()->flash('error', (string)trans('firefly.invalid_link_selection'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }

        $alreadyLinked = $this->repository->findLink($journal, $other);

        if ($other->id === $journal->id) {
            session()->flash('error', (string)trans('firefly.journals_link_to_self'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }

        if ($alreadyLinked) {
            session()->flash('error', (string)trans('firefly.journals_error_linked'));

            return redirect(route('transactions.show', [$journal->transaction_group_id]));
        }
        Log::debug(sprintf('Journal is %d, opposing is %d', $journal->id, $other->id));
        $this->repository->storeLink($linkInfo, $other, $journal);
        session()->flash('success', (string)trans('firefly.journals_linked'));

        return redirect(route('transactions.show', [$journal->transaction_group_id]));
    }

    /**
     * Switch link from A <> B to B <> A.
     *
     * @param TransactionJournalLink $link
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function switchLink(TransactionJournalLink $link)
    {
        $this->repository->switchLink($link);

        return redirect(URL::previous());
    }
}

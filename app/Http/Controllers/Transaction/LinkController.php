<?php
/**
 * LinkController.php
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

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalLinkRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Log;
use Preferences;
use Session;
use URL;

/**
 * Class LinkController.
 */
class LinkController extends Controller
{
    /** @var JournalRepositoryInterface */
    private $journalRepository;
    /** @var LinkTypeRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                $this->journalRepository = app(JournalRepositoryInterface::class);
                $this->repository        = app(LinkTypeRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(TransactionJournalLink $link)
    {
        $subTitleIcon = 'fa-link';
        $subTitle     = trans('breadcrumbs.delete_journal_link');
        $this->rememberPreviousUri('journal_links.delete.uri');

        return view('transactions.links.delete', compact('link', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(TransactionJournalLink $link)
    {
        $this->repository->destroyLink($link);

        Session::flash('success', strval(trans('firefly.deleted_link')));
        Preferences::mark();

        return redirect(strval(session('journal_links.delete.uri')));
    }

    /**
     * @param JournalLinkRequest $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(JournalLinkRequest $request, TransactionJournal $journal)
    {

        Log::debug('We are here (store)');
        $linkInfo = $request->getLinkInfo();
        if (0 === $linkInfo['transaction_journal_id']) {
            Session::flash('error', trans('firefly.invalid_link_selection'));

            return redirect(route('transactions.show', [$journal->id]));
        }
        $other         = $this->journalRepository->find($linkInfo['transaction_journal_id']);
        $alreadyLinked = $this->repository->findLink($journal, $other);
        if ($alreadyLinked) {
            Session::flash('error', trans('firefly.journals_error_linked'));

            return redirect(route('transactions.show', [$journal->id]));
        }
        Log::debug(sprintf('Journal is %d, opposing is %d', $journal->id, $other->id));
        $this->repository->storeLink($linkInfo, $other, $journal);
        Session::flash('success', trans('firefly.journals_linked'));

        return redirect(route('transactions.show', [$journal->id]));
    }

    /**
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

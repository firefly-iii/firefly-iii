<?php
/**
 * LinkController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
use View;

/**
 * Class LinkController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 */
class LinkController extends Controller
{


    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.transactions'));
                View::share('mainTitleIcon', 'fa-repeat');

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
     * @param LinkTypeRepositoryInterface $repository
     * @param TransactionJournalLink      $link
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(LinkTypeRepositoryInterface $repository, TransactionJournalLink $link)
    {
        $repository->destroyLink($link);

        Session::flash('success', strval(trans('firefly.deleted_link')));
        Preferences::mark();

        return redirect(strval(session('journal_links.delete.uri')));
    }

    /**
     * @param JournalLinkRequest          $request
     * @param LinkTypeRepositoryInterface $repository
     * @param JournalRepositoryInterface  $journalRepository
     * @param TransactionJournal          $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(
        JournalLinkRequest $request, LinkTypeRepositoryInterface $repository, JournalRepositoryInterface $journalRepository, TransactionJournal $journal
    ) {
        $linkInfo      = $request->getLinkInfo();
        $linkType      = $repository->find($linkInfo['link_type_id']);
        $other         = $journalRepository->find($linkInfo['transaction_journal_id']);
        $alreadyLinked = $repository->findLink($journal, $other);
        if ($alreadyLinked) {
            Session::flash('error', trans('firefly.journals_error_linked'));

            return redirect(route('transactions.show', $journal->id));
        }
        Log::debug(sprintf('Journal is %d, opposing is %d', $journal->id, $other->id));

        $journalLink = new TransactionJournalLink;
        $journalLink->linkType()->associate($linkType);
        if ($linkInfo['direction'] === 'inward') {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->inward, $other->id, $journal->id));
            $journalLink->source()->associate($other);
            $journalLink->destination()->associate($journal);
        }

        if ($linkInfo['direction'] === 'outward') {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->outward, $journal->id, $other->id));
            $journalLink->source()->associate($journal);
            $journalLink->destination()->associate($other);
        }

        $journalLink->comment = $linkInfo['comments'];
        $journalLink->save();
        Session::flash('success', trans('firefly.journals_linked'));

        return redirect(route('transactions.show', $journal->id));
    }

    public function switch(LinkTypeRepositoryInterface $repository, TransactionJournalLink $link)
    {

        $repository->switchLink($link);

        return redirect(URL::previous());
    }

}

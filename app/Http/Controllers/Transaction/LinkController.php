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


use FireflyIII\Http\Requests\JournalLinkRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Log;
use Session;

class LinkController
{

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
        $linkType = $request->get('link_type');
        $parts    = explode('_', $linkType);
        if (count($parts) !== 2) {
            Session::flash('error', trans('firefly.invalid_link_data'));

            return redirect(route('transactions.show', $journal->id));
        }
        if (!in_array($parts[1], ['inward', 'outward'])) {
            Session::flash('error', trans('firefly.invalid_link_data'));

            return redirect(route('transactions.show', $journal->id));
        }
        $linkTypeId = intval($parts[0]);
        $linkType   = $repository->find($linkTypeId);
        if ($linkType->id !== $linkTypeId) {
            Session::flash('error', trans('firefly.invalid_link_data'));

            return redirect(route('transactions.show', $journal->id));
        }
        Log::debug('Will link using linktype', $linkType->toArray());
        $linkJournalId = intval($request->get('link_journal_id'));

        if ($linkJournalId === 0 && ctype_digit($request->string('link_other'))) {
            $linkJournalId = intval($request->string('link_other'));
        }

        $opposing = $journalRepository->find($linkJournalId);
        $result   = $repository->findLink($journal, $opposing);
        if ($result) {
            Session::flash('error', trans('firefly.journals_error_linked'));

            return redirect(route('transactions.show', $journal->id));
        }
        Log::debug(sprintf('Journal is %d, opposing is %d', $journal->id, $opposing->id));

        $journalLink = new TransactionJournalLink;
        $journalLink->linkType()->associate($linkType);
        if ($parts[1] === 'inward') {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->inward, $opposing->id, $journal->id));
            $journalLink->source()->associate($opposing);
            $journalLink->destination()->associate($journal);
        }
        if ($parts[1] === 'outward') {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->outward, $journal->id, $opposing->id));
            $journalLink->source()->associate($journal);
            $journalLink->destination()->associate($opposing);
        }
        $journalLink->comment = strlen($request->string('comments')) > 0 ? $request->string('comments') : null;
        $journalLink->save();
        Session::flash('success', trans('firefly.journals_linked'));

        return redirect(route('transactions.show', $journal->id));
    }

}
<?php

/**
 * DeleteController.php
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

use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    private TransactionGroupRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                $this->repository = app(TransactionGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function delete(TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group);
        }

        app('log')->debug(sprintf('Start of delete view for group #%d', $group->id));

        $journal    = $group->transactionJournals->first();
        if (null === $journal) {
            throw new NotFoundHttpException();
        }
        $objectType = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle   = (string)trans('firefly.delete_'.$objectType, ['description' => $group->title ?? $journal->description]);
        $previous   = app('steam')->getSafePreviousUrl();
        // put previous url in session
        app('log')->debug('Will try to remember previous URL');
        $this->rememberPreviousUrl('transactions.delete.url');

        return view('transactions.delete', compact('group', 'journal', 'subTitle', 'objectType', 'previous'));
    }

    /**
     * Actually destroys the journal.
     */
    public function destroy(TransactionGroup $group): Redirector|RedirectResponse
    {
        app('log')->debug(sprintf('Now in %s(#%d).', __METHOD__, $group->id));
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group);
        }

        $journal    = $group->transactionJournals->first();
        if (null === $journal) {
            throw new NotFoundHttpException();
        }
        $objectType = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        session()->flash('success', (string)trans('firefly.deleted_'.strtolower($objectType), ['description' => $group->title ?? $journal->description]));

        // grab asset account(s) from group:
        $accounts   = [];

        /** @var TransactionJournal $currentJournal */
        foreach ($group->transactionJournals as $currentJournal) {
            /** @var Transaction $transaction */
            foreach ($currentJournal->transactions as $transaction) {
                $type = $transaction->account->accountType->type;
                // if is valid liability, trigger event!
                if (in_array($type, config('firefly.valid_liabilities'), true)) {
                    $accounts[] = $transaction->account;
                }
            }
        }

        $this->repository->destroy($group);

        /** @var Account $account */
        foreach ($accounts as $account) {
            app('log')->debug(sprintf('Now going to trigger updated account event for account #%d', $account->id));
            event(new UpdatedAccount($account));
        }
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('transactions.delete.url'));
    }
}

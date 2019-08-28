<?php
declare(strict_types=1);
/**
 * DeleteController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Transaction;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Http\RedirectResponse;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use URL;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    use UserNavigation;
    /** @var TransactionGroupRepositoryInterface */
    private $repository;

    /**
     * IndexController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                $this->repository = app(TransactionGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionGroup $group
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group); // @codeCoverageIgnore
        }

        Log::debug(sprintf('Start of delete view for group #%d', $group->id));

        $journal = $group->transactionJournals->first();
        if (null === $journal) {
            throw new NotFoundHttpException;
        }
        $objectType = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle   = (string)trans('firefly.delete_' . $objectType, ['description' => $group->title ?? $journal->description]);
        $previous   = URL::previous(route('index'));
        // put previous url in session
        Log::debug('Will try to remember previous URI');
        $this->rememberPreviousUri('transactions.delete.uri');

        return view('transactions.delete', compact('group', 'journal', 'subTitle', 'objectType', 'previous'));
    }

    /**
     * Actually destroys the journal.
     *
     * @param TransactionGroup $group
     *
     * @return RedirectResponse
     */
    public function destroy(TransactionGroup $group): RedirectResponse
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group); // @codeCoverageIgnore
        }

        $journal = $group->transactionJournals->first();
        if (null === $journal) {
            throw new NotFoundHttpException;
        }
        $objectType = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        session()->flash('success', (string)trans('firefly.deleted_' . strtolower($objectType), ['description' => $group->title ?? $journal->description]));

        $this->repository->destroy($group);

        app('preferences')->mark();

        return redirect($this->getPreviousUri('transactions.delete.uri'));
    }


}

<?php
/**
 * BulkController.php
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
use FireflyIII\Http\Requests\BulkEditJournalRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BulkController
 */
class BulkController extends Controller
{
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;


    /**
     * BulkController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

    /**
     * Edit a set of journals in bulk.
     *
     * TODO user wont be able to tell if journal is part of split.
     *
     * @param Collection $journals
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(array $journals)
    {
        $subTitle = (string)trans('firefly.mass_bulk_journals');

        $this->rememberPreviousUri('transactions.bulk-edit.uri');

        // make amounts positive.

        // get list of budgets:
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgetList = app('expandedform')->makeSelectListWithEmpty($repository->getActiveBudgets());

        return view('transactions.bulk.edit', compact('journals', 'subTitle', 'budgetList'));
    }


    /**
     * Update all journals.
     *
     * @param BulkEditJournalRequest $request
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function update(BulkEditJournalRequest $request)
    {
        $journalIds     = $request->get('journals');
        $journalIds     = is_array($journalIds) ? $journalIds : [];
        $ignoreCategory = 1 === (int)$request->get('ignore_category');
        $ignoreBudget   = 1 === (int)$request->get('ignore_budget');
        $ignoreTags     = 1 === (int)$request->get('ignore_tags');
        $count          = 0;

        foreach ($journalIds as $journalId) {
            $journalId = (int)$journalId;
            $journal   = $this->repository->findNull($journalId);
            if (null !== $journal) {
                $resultA = $this->updateJournalBudget($journal, $ignoreBudget, $request->integer('budget_id'));
                $resultB = $this->updateJournalTags($journal, $ignoreTags, explode(',', $request->string('tags')));
                $resultC = $this->updateJournalCategory($journal, $ignoreCategory, $request->string('category'));
                if ($resultA || $resultB || $resultC) {
                    $count++;
                }
            }
        }
        app('preferences')->mark();
        $request->session()->flash('success', (string)trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.bulk-edit.uri'));
    }

    /**
     * @param TransactionJournal $journal
     * @param bool $ignoreUpdate
     * @param array $tags
     * @return bool
     */
    private function updateJournalTags(TransactionJournal $journal, bool $ignoreUpdate, array $tags): bool
    {

        if (true === $ignoreUpdate) {
            return false;
        }
        Log::debug(sprintf('Set tags to %s', implode(',', $tags)));
        $this->repository->updateTags($journal, $tags);

        return true;
    }

    /**
     * @param TransactionJournal $journal
     * @param bool $ignoreUpdate
     * @param string $category
     * @return bool
     */
    private function updateJournalCategory(TransactionJournal $journal, bool $ignoreUpdate, string $category): bool
    {
        if (true === $ignoreUpdate) {
            return false;
        }
        Log::debug(sprintf('Set budget to %s', $category));
        $this->repository->updateCategory($journal, $category);

        return true;
    }

    /**
     * @param TransactionJournal $journal
     * @param bool $ignoreUpdate
     * @param int $budgetId
     * @return bool
     */
    private function updateJournalBudget(TransactionJournal $journal, bool $ignoreUpdate, int $budgetId): bool
    {
        if (true === $ignoreUpdate) {
            return false;
        }
        Log::debug(sprintf('Set budget to %d', $budgetId));
        $this->repository->updateBudget($journal, $budgetId);

        return true;
    }
}

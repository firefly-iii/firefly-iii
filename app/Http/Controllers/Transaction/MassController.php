<?php
/**
 * MassController.php
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

use Carbon\Carbon;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use Illuminate\View\View as IlluminateView;
use InvalidArgumentException;
use Log;

/**
 * Class MassController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassController extends Controller
{
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * MassController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');
                $this->repository = app(JournalRepositoryInterface::class);
                return $next($request);
            }
        );
    }

    /**
     * Mass delete transactions.
     *
     * @param array $journals
     *
     * @return IlluminateView
     */
    public function delete(array $journals): IlluminateView
    {
        $subTitle = (string)trans('firefly.mass_delete_journals');

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-delete.uri');

        return view('transactions.mass.delete', compact('journals', 'subTitle'));
    }

    /**
     * Do the mass delete.
     *
     * @param MassDeleteJournalRequest $request
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function destroy(MassDeleteJournalRequest $request)
    {
        $ids   = $request->get('confirm_mass_delete');
        $count = 0;
        if (is_array($ids)) {
            /** @var string $journalId */
            foreach ($ids as $journalId) {

                /** @var TransactionJournal $journal */
                $journal = $this->repository->findNull((int)$journalId);
                if (null !== $journal && (int)$journalId === $journal->id) {
                    $this->repository->destroyJournal($journal);
                    ++$count;
                }
            }
        }


        app('preferences')->mark();
        session()->flash('success', (string)trans('firefly.mass_deleted_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-delete.uri'));
    }

    /**
     * Mass edit of journals.
     *
     * @param array $journals
     *
     * @return IlluminateView
     */
    public function edit(array $journals): IlluminateView
    {
        $subTitle = (string)trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        // valid withdrawal sources:
        $array             = array_keys(config(sprintf('firefly.source_dests.%s', TransactionType::WITHDRAWAL)));
        $withdrawalSources = $repository->getAccountsByType($array);

        // valid deposit destinations:
        $array               = config(sprintf('firefly.source_dests.%s.%s', TransactionType::DEPOSIT, AccountType::REVENUE));
        $depositDestinations = $repository->getAccountsByType($array);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgets          = $budgetRepository->getBudgets();

        // reverse amounts
        foreach ($journals as $index => $journal) {
            $journals[$index]['amount']         = app('steam')->positive($journal['amount']);
            $journals[$index]['foreign_amount'] = null === $journal['foreign_amount'] ?
                null : app('steam')->positive($journal['foreign_amount']);
        }

        $this->rememberPreviousUri('transactions.mass-edit.uri');

        return view('transactions.mass.edit', compact('journals', 'subTitle', 'withdrawalSources', 'depositDestinations', 'budgets'));
    }

    /**
     * Mass update of journals.
     *
     * @param MassEditJournalRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function update(MassEditJournalRequest $request)
    {
        $journalIds = $request->get('journals');
        if (!is_array($journalIds)) {
            // TODO something error.
            throw new FireflyException('This is not an array.'); // @codeCoverageIgnore
        }
        $count = 0;
        /** @var string $journalId */
        foreach ($journalIds as $journalId) {
            $integer = (int)$journalId;
            try {
                $this->updateJournal($integer, $request);
                $count++;
            } catch (FireflyException $e) {  // @codeCoverageIgnore
                // do something with error.
                //echo $e->getMessage();
                //exit;
            }
        }

        app('preferences')->mark();
        session()->flash('success', (string)trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-edit.uri'));
    }

    /**
     * @param int $journalId
     * @param MassEditJournalRequest $request
     * @throws FireflyException
     */
    private function updateJournal(int $journalId, MassEditJournalRequest $request): void
    {
        $journal = $this->repository->findNull($journalId);
        if (null === $journal) {
            throw new FireflyException(sprintf('Trying to edit non-existent or deleted journal #%d', $journalId)); // @codeCoverageIgnore
        }
        $service = app(JournalUpdateService::class);
        // for each field, call the update service.
        $service->setTransactionJournal($journal);

        $data = [
            'date'             => $this->getDateFromRequest($request, $journal->id, 'date'),
            'description'      => $this->getStringFromRequest($request, $journal->id, 'description'),
            'source_id'        => $this->getIntFromRequest($request, $journal->id, 'source_id'),
            'source_name'      => $this->getStringFromRequest($request, $journal->id, 'source_name'),
            'destination_id'   => $this->getIntFromRequest($request, $journal->id, 'destination_id'),
            'destination_name' => $this->getStringFromRequest($request, $journal->id, 'destination_name'),
            'budget_id'        => $this->getIntFromRequest($request, $journal->id, 'budget_id'),
            'category_name'    => $this->getStringFromRequest($request, $journal->id, 'category'),
            'amount'           => $this->getStringFromRequest($request, $journal->id, 'amount'),
            'foreign_amount'   => $this->getStringFromRequest($request, $journal->id, 'foreign_amount'),
        ];
        Log::debug(sprintf('Will update journal #%d with data.', $journal->id), $data);

        // call service to update.
        $service->setData($data);
        $service->update();
        // trigger rules
        event(new UpdatedTransactionGroup($journal->transactionGroup));
    }

    /**
     * @param MassEditJournalRequest $request
     * @param int $journalId
     * @param string $string
     * @return int|null
     * @codeCoverageIgnore
     */
    private function getIntFromRequest(MassEditJournalRequest $request, int $journalId, string $string): ?int
    {
        $value = $request->get($string);
        if (!is_array($value)) {
            return null;
        }
        if (!isset($value[$journalId])) {
            return null;
        }

        return (int)$value[$journalId];
    }

    /**
     * @param MassEditJournalRequest $request
     * @param int $journalId
     * @param string $string
     * @return string|null
     * @codeCoverageIgnore
     */
    private function getStringFromRequest(MassEditJournalRequest $request, int $journalId, string $string): ?string
    {
        $value = $request->get($string);
        if (!is_array($value)) {
            return null;
        }
        if (!isset($value[$journalId])) {
            return null;
        }

        return (string)$value[$journalId];
    }

    /**
     * @param MassEditJournalRequest $request
     * @param int $journalId
     * @param string $string
     * @return Carbon|null
     * @codeCoverageIgnore
     */
    private function getDateFromRequest(MassEditJournalRequest $request, int $journalId, string $string): ?Carbon
    {
        $value = $request->get($string);
        if (!is_array($value)) {
            return null;
        }
        if (!isset($value[$journalId])) {
            return null;
        }
        try {
            $carbon = Carbon::parse($value[$journalId]);
        } catch (InvalidArgumentException $e) {
            $e->getMessage();

            return null;
        }

        return $carbon;
    }
}

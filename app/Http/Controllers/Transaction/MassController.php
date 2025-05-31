<?php

/**
 * MassController.php
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

use InvalidArgumentException;
use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View as IlluminateView;

/**
 * Class MassController.
 */
class MassController extends Controller
{
    private JournalRepositoryInterface $repository;

    /**
     * MassController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');
                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Mass delete transactions.
     */
    public function delete(array $journals): IlluminateView
    {
        $subTitle = (string) trans('firefly.mass_delete_journals');

        // put previous url in session
        $this->rememberPreviousUrl('transactions.mass-delete.url');

        return view('transactions.mass.delete', compact('journals', 'subTitle'));
    }

    /**
     * Do the mass delete.
     *
     * @return Application|Redirector|RedirectResponse
     */
    public function destroy(MassDeleteJournalRequest $request)
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $ids   = $request->get('confirm_mass_delete');
        $count = 0;
        if (is_array($ids)) {
            app('log')->debug('Array of IDs', $ids);

            /** @var string $journalId */
            foreach ($ids as $journalId) {
                app('log')->debug(sprintf('Searching for ID #%d', $journalId));

                /** @var null|TransactionJournal $journal */
                $journal = $this->repository->find((int) $journalId);
                if (null !== $journal && (int) $journalId === $journal->id) {
                    $this->repository->destroyJournal($journal);
                    ++$count;
                    app('log')->debug(sprintf('Deleted transaction journal #%d', $journalId));

                    continue;
                }
                app('log')->debug(sprintf('Could not find transaction journal #%d', $journalId));
            }
        }
        app('preferences')->mark();
        session()->flash('success', trans_choice('firefly.mass_deleted_transactions_success', $count));

        // redirect to previous URL:
        return redirect($this->getPreviousUrl('transactions.mass-delete.url'));
    }

    /**
     * Mass edit of journals.
     */
    public function edit(array $journals): IlluminateView
    {
        $subTitle            = (string) trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository   = app(AccountRepositoryInterface::class);

        // valid withdrawal sources:
        $array               = array_keys(config(sprintf('firefly.source_dests.%s', TransactionTypeEnum::WITHDRAWAL->value)));
        $withdrawalSources   = $accountRepository->getAccountsByType($array);

        // valid deposit destinations:
        $array               = config(sprintf('firefly.source_dests.%s.%s', TransactionTypeEnum::DEPOSIT->value, AccountTypeEnum::REVENUE->value));
        $depositDestinations = $accountRepository->getAccountsByType($array);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository    = app(BudgetRepositoryInterface::class);
        $budgets             = $budgetRepository->getBudgets();

        // reverse amounts
        foreach ($journals as $index => $journal) {
            $journals[$index]['amount']         = app('steam')->bcround(app('steam')->positive($journal['amount']), $journal['currency_decimal_places']);
            $journals[$index]['foreign_amount'] = null === $journal['foreign_amount']
                ? null : app('steam')->positive($journal['foreign_amount']);
        }

        $this->rememberPreviousUrl('transactions.mass-edit.url');

        return view('transactions.mass.edit', compact('journals', 'subTitle', 'withdrawalSources', 'depositDestinations', 'budgets'));
    }

    /**
     * Mass update of journals.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function update(MassEditJournalRequest $request)
    {
        $journalIds = $request->get('journals');
        if (!is_array($journalIds)) {
            // TODO this is a weird error, should be caught.
            throw new FireflyException('This is not an array.');
        }
        $count      = 0;

        /** @var string $journalId */
        foreach ($journalIds as $journalId) {
            $integer = (int) $journalId;

            try {
                $this->updateJournal($integer, $request);
                ++$count;
            } catch (FireflyException) {
                // @ignoreException
            }
        }

        app('preferences')->mark();
        session()->flash('success', trans_choice('firefly.mass_edited_transactions_success', $count));

        // redirect to previous URL:
        return redirect($this->getPreviousUrl('transactions.mass-edit.url'));
    }

    /**
     * @throws FireflyException
     */
    private function updateJournal(int $journalId, MassEditJournalRequest $request): void
    {
        $journal       = $this->repository->find($journalId);
        if (!$journal instanceof TransactionJournal) {
            throw new FireflyException(sprintf('Trying to edit non-existent or deleted journal #%d', $journalId));
        }
        $service       = app(JournalUpdateService::class);
        // for each field, call the update service.
        $service->setTransactionJournal($journal);

        $data          = [
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
        app('log')->debug(sprintf('Will update journal #%d with data.', $journal->id), $data);

        // call service to update.
        $service->setData($data);
        $service->update();
        // trigger rules
        $amountChanged = $service->isAmountChanged();
        event(new UpdatedTransactionGroup($journal->transactionGroup, true, true, $amountChanged));
    }

    private function getDateFromRequest(MassEditJournalRequest $request, int $journalId, string $key): ?Carbon
    {
        $value = $request->get($key);
        if (!is_array($value)) {
            return null;
        }
        if (!array_key_exists($journalId, $value)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($value[$journalId]);
        } catch (InvalidArgumentException $e) {
            Log::warning(sprintf('Could not parse "%s" but dont mind', $value[$journalId]));
            Log::warning($e->getMessage());

            return null;
        }

        return $carbon;
    }

    private function getStringFromRequest(MassEditJournalRequest $request, int $journalId, string $string): ?string
    {
        $value = $request->get($string);
        if (!is_array($value)) {
            return null;
        }
        if (!array_key_exists($journalId, $value)) {
            return null;
        }

        return (string) $value[$journalId];
    }

    private function getIntFromRequest(MassEditJournalRequest $request, int $journalId, string $string): ?int
    {
        $value = $request->get($string);
        if (!is_array($value)) {
            return null;
        }
        if (!array_key_exists($journalId, $value)) {
            return null;
        }

        return (int) $value[$journalId];
    }
}

<?php
/**
 * ReportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Popup;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Binder\AccountList;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Response;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers\Popup
 */
class ReportController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws FireflyException
     */
    public function info(Request $request)
    {
        $attributes = $request->get('attributes') ?? [];
        $attributes = $this->parseAttributes($attributes);

        View::share('start', $attributes['startDate']);
        View::share('end', $attributes['endDate']);

        switch ($attributes['location']) {
            default:
                throw new FireflyException('Firefly cannot handle "' . e($attributes['location']) . '" ');
            case 'budget-spent-amount':
                $html = $this->budgetSpentAmount($attributes);

                break;
            case 'expense-entry':
                $html = $this->expenseEntry($attributes);
                break;
            case 'income-entry':
                $html = $this->incomeEntry($attributes);
                break;
            case 'category-entry':
                $html = $this->categoryEntry($attributes);
                break;
            case 'balance-amount':
                $html = $this->balanceAmount($attributes);
                break;
        }

        return Response::json(['html' => $html]);


    }

    /**
     * @param $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function balanceAmount(array $attributes): string
    {
        $role = intval($attributes['role']);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budget           = $budgetRepository->find(intval($attributes['budgetId']));

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $account = $repository->find(intval($attributes['accountId']));

        switch (true) {
            case ($role === BalanceLine::ROLE_DEFAULTROLE && !is_null($budget->id)):
                $journals = $budgetRepository->journalsInPeriod(
                    new Collection([$budget]), new Collection([$account]), $attributes['startDate'], $attributes['endDate']
                );
                break;
            case ($role === BalanceLine::ROLE_DEFAULTROLE && is_null($budget->id)):
                $budget->name = strval(trans('firefly.no_budget'));
                $journals     = $budgetRepository->journalsInPeriodWithoutBudget($attributes['accounts'], $attributes['startDate'], $attributes['endDate']);
                break;
            case ($role === BalanceLine::ROLE_DIFFROLE):
                // journals no budget, not corrected by a tag.
                $journals     = $budgetRepository->journalsInPeriodWithoutBudget($attributes['accounts'], $attributes['startDate'], $attributes['endDate']);
                $budget->name = strval(trans('firefly.leftUnbalanced'));
                $journals     = $journals->filter(
                    function (TransactionJournal $journal) {
                        $tags = $journal->tags()->where('tagMode', 'balancingAct')->count();
                        if ($tags === 0) {
                            return true;
                        }

                        return false;
                    }
                );
                break;
            case ($role === BalanceLine::ROLE_TAGROLE):
                throw new FireflyException('Firefly cannot handle this type of info-button (BalanceLine::TagRole)');
        }
        $view = view('popup.report.balance-amount', compact('journals', 'budget', 'account'))->render();

        return $view;
    }

    /**
     * Returns all expenses inside the given budget for the given accounts.
     *
     * @param array $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function budgetSpentAmount(array $attributes): string
    {
        // need to find the budget
        // then search for expenses in the given period
        // list them in some table format.
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budget     = $repository->find(intval($attributes['budgetId']));
        if (is_null($budget->id)) {
            $journals = $repository->journalsInPeriodWithoutBudget($attributes['accounts'], $attributes['startDate'], $attributes['endDate']);
        } else {
            // get all expenses in budget in period:
            $journals = $repository->journalsInPeriod(new Collection([$budget]), $attributes['accounts'], $attributes['startDate'], $attributes['endDate']);
        }

        $view = view('popup.report.budget-spent-amount', compact('journals', 'budget'))->render();

        return $view;
    }

    /**
     * Returns all expenses in category in range.
     *
     * @param $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function categoryEntry(array $attributes): string
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $category   = $repository->find(intval($attributes['categoryId']));
        $types      = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        // get journal collector instead:
        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts($attributes['accounts'])->setTypes($types)
                  ->setRange($attributes['startDate'], $attributes['endDate'])
                  ->setCategory($category);
        $journals = $collector->getJournals(); // 7193

        $view = view('popup.report.category-entry', compact('journals', 'category'))->render();

        return $view;
    }

    /**
     * Returns all the expenses that went to the given expense account.
     *
     * @param $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function expenseEntry(array $attributes): string
    {
        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $account  = $repository->find(intval($attributes['accountId']));
        $types    = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        $journals = $tasker->getJournalsInPeriod(new Collection([$account]), $types, $attributes['startDate'], $attributes['endDate']);
        $report   = $attributes['accounts']->pluck('id')->toArray(); // accounts used in this report

        // filter for transfers and withdrawals TO the given $account
        $journals = $journals->filter(
            function (Transaction $transaction) use ($report) {
                // get the destinations:
                $sources = TransactionJournal::sourceAccountList($transaction->transactionJournal)->pluck('id')->toArray();

                // do these intersect with the current list?
                return !empty(array_intersect($report, $sources));
            }
        );

        $view = view('popup.report.expense-entry', compact('journals', 'account'))->render();

        return $view;
    }

    /**
     * Returns all the incomes that went to the given asset account.
     *
     * @param $attributes
     *
     * @return string
     * @throws FireflyException
     */
    private function incomeEntry(array $attributes): string
    {
        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $account    = $repository->find(intval($attributes['accountId']));
        $types      = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $journals   = $tasker->getJournalsInPeriod(new Collection([$account]), $types, $attributes['startDate'], $attributes['endDate']);
        $report     = $attributes['accounts']->pluck('id')->toArray(); // accounts used in this report

        // filter the set so the destinations outside of $attributes['accounts'] are not included.
        $journals = $journals->filter(
            function (Transaction $transaction) use ($report) {
                // get the destinations:
                $destinations = TransactionJournal::destinationAccountList($transaction->transactionJournal)->pluck('id')->toArray();

                // do these intersect with the current list?
                return !empty(array_intersect($report, $destinations));
            }
        );

        $view = view('popup.report.income-entry', compact('journals', 'account'))->render();

        return $view;
    }

    /**
     * @param array $attributes
     *
     * @return array
     * @throws FireflyException
     */
    private function parseAttributes(array $attributes): array
    {
        $attributes['location'] = $attributes['location'] ?? '';
        $attributes['accounts'] = AccountList::routeBinder($attributes['accounts'] ?? '', '');
        try {
            $attributes['startDate'] = Carbon::createFromFormat('Ymd', $attributes['startDate']);
        } catch (InvalidArgumentException $e) {
            throw new FireflyException('Could not parse start date "' . e($attributes['startDate']) . '".');
        }

        try {
            $attributes['endDate'] = Carbon::createFromFormat('Ymd', $attributes['endDate']);
        } catch (InvalidArgumentException $e) {
            throw new FireflyException('Could not parse start date "' . e($attributes['endDate']) . '".');
        }


        return $attributes;
    }

}

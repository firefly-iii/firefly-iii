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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Popup;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Binder\AccountList;
use Illuminate\Http\Request;
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

    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var BudgetRepositoryInterface */
    private $budgetRepository;
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;
    /** @var PopupReportInterface */
    private $popupHelper;


    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {

                /** @var AccountRepositoryInterface $repository */
                $this->accountRepository = app(AccountRepositoryInterface::class);

                /** @var BudgetRepositoryInterface $repository */
                $this->budgetRepository = app(BudgetRepositoryInterface::class);

                /** @var CategoryRepositoryInterface categoryRepository */
                $this->categoryRepository = app(CategoryRepositoryInterface::class);

                /** @var PopupReportInterface popupHelper */
                $this->popupHelper = app(PopupReportInterface::class);

                return $next($request);
            }
        );

    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws FireflyException
     */
    public function general(Request $request)
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
        $role    = intval($attributes['role']);
        $budget  = $this->budgetRepository->find(intval($attributes['budgetId']));
        $account = $this->accountRepository->find(intval($attributes['accountId']));

        switch (true) {
            case ($role === BalanceLine::ROLE_DEFAULTROLE && !is_null($budget->id)):
                // normal row with a budget:
                $journals = $this->popupHelper->balanceForBudget($budget, $account, $attributes);
                break;
            case ($role === BalanceLine::ROLE_DEFAULTROLE && is_null($budget->id)):
                // normal row without a budget:
                $journals     = $this->popupHelper->balanceForNoBudget($account, $attributes);
                $budget->name = strval(trans('firefly.no_budget'));
                break;
            case ($role === BalanceLine::ROLE_DIFFROLE):
                $journals     = $this->popupHelper->balanceDifference($account, $attributes);
                $budget->name = strval(trans('firefly.leftUnbalanced'));
                break;
            case ($role === BalanceLine::ROLE_TAGROLE):
                // row with tag info.
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
        $budget   = $this->budgetRepository->find(intval($attributes['budgetId']));
        $journals = $this->popupHelper->byBudget($budget, $attributes);
        $view     = view('popup.report.budget-spent-amount', compact('journals', 'budget'))->render();

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
        $category = $this->categoryRepository->find(intval($attributes['categoryId']));
        $journals = $this->popupHelper->byCategory($category, $attributes);
        $view     = view('popup.report.category-entry', compact('journals', 'category'))->render();

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
        $account  = $this->accountRepository->find(intval($attributes['accountId']));
        $journals = $this->popupHelper->byExpenses($account, $attributes);
        $view     = view('popup.report.expense-entry', compact('journals', 'account'))->render();

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
        $account  = $this->accountRepository->find(intval($attributes['accountId']));
        $journals = $this->popupHelper->byIncome($account, $attributes);
        $view     = view('popup.report.income-entry', compact('journals', 'account'))->render();

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

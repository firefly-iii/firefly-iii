<?php
/**
 * BudgetReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class BudgetReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class BudgetReportHelper implements BudgetReportHelperInterface
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * BudgetReportHelper constructor.
     *
     * @param BudgetRepositoryInterface $repository
     */
    public function __construct(BudgetRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // at 43, its ok.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function budgetYearOverview(Carbon $start, Carbon $end, Collection $accounts): Collection
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-year');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $current = clone $start;
        $return  = new Collection;
        $set     = $this->repository->getBudgets();
        $budgets = [];
        $spent   = [];
        $headers = $this->createYearHeaders($current, $end);

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $id           = $budget->id;
            $budgets[$id] = $budget->name;
            $current      = clone $start;
            $budgetData   = $this->getBudgetSpentData($current, $end, $budget, $accounts);
            $sum          = $budgetData['sum'];
            $spent[$id]   = $budgetData['spent'];

            if (bccomp('0', $sum) === 0) {
                // not spent anything.
                unset($spent[$id]);
                unset($budgets[$id]);
            }
        }

        $return->put('headers', $headers);
        $return->put('budgets', $budgets);
        $return->put('spent', $spent);

        $cache->store($return);

        return $return;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts): BudgetCollection
    {
        $object = new BudgetCollection;
        $set    = $this->repository->getBudgets();

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $repetitions = $budget->limitrepetitions()->before($end)->after($start)->get();

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {
                // spent for budget in time range:
                $spent = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);

                if ($spent > 0) {
                    $budgetLine = new BudgetLine;
                    $budgetLine->setBudget($budget)->setOverspent($spent);
                    $object->addOverspent($spent)->addBudgetLine($budgetLine);
                }
                continue;
            }
            // one or more repetitions for budget:
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $data = $this->calculateExpenses($budget, $repetition, $accounts);

                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget)->setRepetition($repetition)
                           ->setLeft($data['left'])->setSpent($data['expenses'])->setOverspent($data['overspent'])
                           ->setBudgeted(strval($repetition->amount));

                $object->addBudgeted(strval($repetition->amount))->addSpent($data['spent'])
                       ->addLeft($data['left'])->addOverspent($data['overspent'])->addBudgetLine($budgetLine);

            }

        }

        // stuff outside of budgets:

        $noBudget   = $this->repository->spentInPeriodWithoutBudget($accounts, $start, $end);
        $budgetLine = new BudgetLine;
        $budgetLine->setOverspent($noBudget)->setSpent($noBudget);
        $object->addOverspent($noBudget)->addBudgetLine($budgetLine);

        return $object;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBudgetsWithExpenses(Carbon $start, Carbon $end, Collection $accounts): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getActiveBudgets();

        $set = new Collection;
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $total = $repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);
            if (bccomp($total, '0') === -1) {
                $set->push($budget);
            }
        }
        $set = $set->sortBy(
            function (Budget $budget) {
                return $budget->name;
            }
        );

        return $set;
    }

    /**
     * Take the array as returned by CategoryRepositoryInterface::spentPerDay and CategoryRepositoryInterface::earnedByDay
     * and sum up everything in the array in the given range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array  $array
     *
     * @return string
     */
    protected function getSumOfRange(Carbon $start, Carbon $end, array $array)
    {
        $sum          = '0';
        $currentStart = clone $start; // to not mess with the original one
        $currentEnd   = clone $end; // to not mess with the original one

        while ($currentStart <= $currentEnd) {
            $date = $currentStart->format('Y-m-d');
            if (isset($array[$date])) {
                $sum = bcadd($sum, $array[$date]);
            }
            $currentStart->addDay();
        }

        return $sum;
    }

    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param Collection      $accounts
     *
     * @return array
     */
    private function calculateExpenses(Budget $budget, LimitRepetition $repetition, Collection $accounts): array
    {
        $array              = [];
        $expenses           = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $repetition->startdate, $repetition->enddate);
        $array['left']      = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? bcadd($repetition->amount, $expenses) : '0';
        $array['spent']     = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? $expenses : '0';
        $array['overspent'] = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? '0' : bcadd($expenses, $repetition->amount);
        $array['expenses']  = $expenses;

        return $array;

    }

    /**
     * @param Carbon $current
     * @param Carbon $end
     *
     * @return array
     */
    private function createYearHeaders(Carbon $current, Carbon $end): array
    {
        $headers = [];
        while ($current < $end) {
            $short           = $current->format('m-Y');
            $headers[$short] = $current->formatLocalized((string)trans('config.month'));
            $current->addMonth();
        }

        return $headers;
    }

    /**
     * @param Carbon     $current
     * @param Carbon     $end
     * @param Budget     $budget
     * @param Collection $accounts
     *
     * @return array
     */
    private function getBudgetSpentData(Carbon $current, Carbon $end, Budget $budget, Collection $accounts): array
    {
        $sum   = '0';
        $spent = [];
        while ($current < $end) {
            $currentEnd = clone $current;
            $currentEnd->endOfMonth();
            $format         = $current->format('m-Y');
            $budgetSpent    = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $current, $currentEnd);
            $spent[$format] = $budgetSpent;
            $sum            = bcadd($sum, $budgetSpent);
            $current->addMonth();
        }

        return [
            'spent' => $spent,
            'sum'   => $sum,
        ];
    }
}

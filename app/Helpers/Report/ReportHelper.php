<?php
declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Bill as BillCollection;
use FireflyIII\Helpers\Collection\BillLine;
use FireflyIII\Helpers\Collection\Category as CategoryCollection;
use FireflyIII\Helpers\Collection\Expense;
use FireflyIII\Helpers\Collection\Income;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /** @var  BudgetRepositoryInterface */
    protected $budgetRepository;
    /** @var ReportQueryInterface */
    protected $query;
    /** @var  TagRepositoryInterface */
    protected $tagRepository;

    /**
     * ReportHelper constructor.
     *
     *
     * @param ReportQueryInterface      $query
     * @param BudgetRepositoryInterface $budgetRepository
     * @param TagRepositoryInterface    $tagRepository
     */
    public function __construct(ReportQueryInterface $query, BudgetRepositoryInterface $budgetRepository, TagRepositoryInterface $tagRepository)
    {
        $this->query            = $query;
        $this->budgetRepository = $budgetRepository;
        $this->tagRepository    = $tagRepository;
    }

    /**
     * This method generates a full report for the given period on all
     * the users bills and their payments.
     *
     * Excludes bills which have not had a payment on the mentioned accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BillCollection
     */
    public function getBillReport(Carbon $start, Carbon $end, Collection $accounts): BillCollection
    {
        /** @var \FireflyIII\Repositories\Bill\BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $bills      = $repository->getBillsForAccounts($accounts);
        $journals   = $repository->getAllJournalsInRange($bills, $start, $end);
        $collection = new BillCollection;

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $billLine = new BillLine;
            $billLine->setBill($bill);
            $billLine->setActive(intval($bill->active) == 1);
            $billLine->setMin($bill->amount_min);
            $billLine->setMax($bill->amount_max);

            // is hit in period?

            $entry = $journals->filter(
                function (TransactionJournal $journal) use ($bill) {
                    return $journal->bill_id === $bill->id;
                }
            );
            $first = $entry->first();
            if (!is_null($first)) {
                $billLine->setTransactionJournalId($first->id);
                $billLine->setAmount($first->journalAmount);
                $billLine->setHit(true);
            } else {
                $billLine->setHit(false);
            }
            if (!(!$billLine->isHit() && !$billLine->isActive())) {
                $collection->addBill($billLine);
            }

        }

        return $collection;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return CategoryCollection
     */
    public function getCategoryReport(Carbon $start, Carbon $end, Collection $accounts): CategoryCollection
    {
        $object = new CategoryCollection;

        /**
         * GET CATEGORIES:
         */
        /** @var \FireflyIII\Repositories\Category\CategoryRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        $set = $repository->spentForAccountsPerMonth($accounts, $start, $end);
        foreach ($set as $category) {
            $object->addCategory($category);
        }

        return $object;
    }

    /**
     * Get a full report on the users expenses during the period for a list of accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Expense
     */
    public function getExpenseReport(Carbon $start, Carbon $end, Collection $accounts): Expense
    {
        $object = new Expense;
        $set    = $this->query->expense($accounts, $start, $end);

        foreach ($set as $entry) {
            $object->addToTotal($entry->journalAmount); // can be positive, if it's a transfer
            $object->addOrCreateExpense($entry);
        }

        return $object;
    }

    /**
     * Get a full report on the users incomes during the period for the given accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Income
     */
    public function getIncomeReport(Carbon $start, Carbon $end, Collection $accounts): Income
    {
        $object = new Income;
        $set    = $this->query->income($accounts, $start, $end);

        foreach ($set as $entry) {
            $object->addToTotal($entry->journalAmount);
            $object->addOrCreateIncome($entry);
        }

        return $object;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date): array
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app('FireflyIII\Helpers\FiscalHelperInterface');
        $start        = clone $date;
        $start->startOfMonth();
        $end = Carbon::now();
        $end->endOfMonth();
        $months = [];

        while ($start <= $end) {
            // current year:
            $year = $fiscalHelper->endOfFiscalYear($start)->year;

            if (!isset($months[$year])) {
                $months[$year] = [
                    'fiscal_start' => $fiscalHelper->startOfFiscalYear($start)->format('Y-m-d'),
                    'fiscal_end'   => $fiscalHelper->endOfFiscalYear($start)->format('Y-m-d'),
                    'start'        => Carbon::createFromDate($year, 1, 1)->format('Y-m-d'),
                    'end'          => Carbon::createFromDate($year, 12, 31)->format('Y-m-d'),
                    'months'       => [],
                ];
            }

            $currentEnd = clone $start;
            $currentEnd->endOfMonth();

            $months[$year]['months'][] = [
                'formatted' => $start->formatLocalized('%B %Y'),
                'start'     => $start->format('Y-m-d'),
                'end'       => $currentEnd->format('Y-m-d'),
                'month'     => $start->month,
                'year'      => $year,
            ];

            // to make the hop to the next month properly:
            $start = clone $currentEnd;
            $start->addDay();
        }

        return $months;
    }

    /**
     * Returns an array of tags and their comparitive size with amounts bla bla.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function tagReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        $ids        = $accounts->pluck('id')->toArray();
        $set        = Tag::
        leftJoin('tag_transaction_journal', 'tags.id', '=', 'tag_transaction_journal.tag_id')
                         ->leftJoin('transaction_journals', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                         ->leftJoin(
                             'transactions AS source', function (JoinClause $join) {
                             $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', '0');
                         }
                         )
                         ->leftJoin(
                             'transactions AS destination', function (JoinClause $join) {
                             $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', '0');
                         }
                         )
                         ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                         ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                         ->where(
                             function (Builder $q) use ($ids) {
                                 $q->whereIn('source.account_id', $ids)
                                   ->whereIn('destination.account_id', $ids, 'xor');
                             }
                         )
                         ->get(['tags.id', 'tags.tag', 'transaction_journals.id as journal_id', 'destination.amount']);
        $collection = [];
        if ($set->count() === 0) {
            return $collection;
        }
        /** @var Tag $entry */
        foreach ($set as $entry) {
            // less than zero? multiply to be above zero.
            $amount = $entry->amount;
            $id     = intval($entry->id);
            if (!isset($collection[$id])) {
                $collection[$id] = [
                    'id'     => $id,
                    'tag'    => $entry->tag,
                    'amount' => $amount,
                ];
            } else {
                $collection[$id]['amount'] = bcadd($collection[$id]['amount'], $amount);
            }
        }

        // cleanup collection (match "fonts")
        $max = strval(max(array_column($collection, 'amount')));
        foreach ($collection as $id => $entry) {
            $size = bcdiv($entry['amount'], $max, 4);
            if (bccomp($size, '0.25') === -1) {
                $size = '0.5';
            }
            $collection[$id]['fontsize'] = $size;
        }

        return $collection;
    }

    /**
     * Take the array as returned by SingleCategoryRepositoryInterface::spentPerDay and SingleCategoryRepositoryInterface::earnedByDay
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
}

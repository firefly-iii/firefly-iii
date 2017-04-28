<?php
/**
 * MetaPieChart.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Report\Support;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Class MetaPieChart
 *
 * @package FireflyIII\Helpers\Chart
 */
class MetaPieChart implements MetaPieChartInterface
{
    /** @var  Collection */
    protected $accounts;
    /** @var  Collection */
    protected $budgets;
    /** @var  Collection */
    protected $categories;
    /** @var bool */
    protected $collectOtherObjects = false;
    /** @var  Carbon */
    protected $end;
    /** @var array */
    protected $grouping
        = [
            'account'  => ['opposing_account_id'],
            'budget'   => ['transaction_journal_budget_id', 'transaction_budget_id'],
            'category' => ['transaction_journal_category_id', 'transaction_category_id'],
            'tag'      => [],
        ];
    /** @var array */
    protected $repositories
        = [
            'account'  => AccountRepositoryInterface::class,
            'budget'   => BudgetRepositoryInterface::class,
            'category' => CategoryRepositoryInterface::class,
            'tag'      => TagRepositoryInterface::class,
        ];
    /** @var  Carbon */
    protected $start;
    /** @var  Collection */
    protected $tags;
    /** @var  string */
    protected $total = '0';
    /** @var  User */
    protected $user;

    public function __construct()
    {
        $this->accounts   = new Collection;
        $this->budgets    = new Collection;
        $this->categories = new Collection;
        $this->tags       = new Collection;
    }

    /**
     * @param string $direction
     * @param string $group
     *
     * @return array
     */
    public function generate(string $direction, string $group): array
    {
        $transactions = $this->getTransactions($direction);
        $grouped      = $this->groupByFields($transactions, $this->grouping[$group]);
        $chartData    = $this->organizeByType($group, $grouped);

        // also collect all other transactions
        if ($this->collectOtherObjects && $direction === 'expense') {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcmul($sum, '-1');
            $sum                                                 = bcsub($sum, $this->total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        if ($this->collectOtherObjects && $direction === 'income') {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setUser($this->user);
            $collector->setAccounts($this->accounts)->setRange($this->start, $this->end)->setTypes([TransactionType::DEPOSIT]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcsub($sum, $this->total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        return $chartData;

    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $accounts
     *
     * @return MetaPieChartInterface
     */
    public function setAccounts(Collection $accounts): MetaPieChartInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $budgets
     *
     * @return MetaPieChartInterface
     */
    public function setBudgets(Collection $budgets): MetaPieChartInterface
    {
        $this->budgets = $budgets;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $categories
     *
     * @return MetaPieChartInterface
     */
    public function setCategories(Collection $categories): MetaPieChartInterface
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param bool $collectOtherObjects
     *
     * @return MetaPieChartInterface
     */
    public function setCollectOtherObjects(bool $collectOtherObjects): MetaPieChartInterface
    {
        $this->collectOtherObjects = $collectOtherObjects;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Carbon $end
     *
     * @return MetaPieChartInterface
     */
    public function setEnd(Carbon $end): MetaPieChartInterface
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Carbon $start
     *
     * @return MetaPieChartInterface
     */
    public function setStart(Carbon $start): MetaPieChartInterface
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $tags
     *
     * @return MetaPieChartInterface
     */
    public function setTags(Collection $tags): MetaPieChartInterface
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param User $user
     *
     * @return MetaPieChartInterface
     */
    public function setUser(User $user): MetaPieChartInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param string $direction
     *
     * @return Collection
     */
    protected function getTransactions(string $direction): Collection
    {
        $types    = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $modifier = -1;
        if ($direction === 'expense') {
            $types    = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
            $modifier = 1;
        }
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->start, $this->end);
        $collector->setTypes($types);
        $collector->withOpposingAccount();

        if ($direction === 'income') {
            $collector->disableFilter();
        }

        if ($this->budgets->count() > 0) {
            $collector->setBudgets($this->budgets);
        }
        if ($this->categories->count() > 0) {
            $collector->setCategories($this->categories);
        }
        if ($this->tags->count() > 0) {
            $collector->setTags($this->tags);
            $collector->withCategoryInformation();
            $collector->withBudgetInformation();
        }

        $accountIds   = $this->accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        Log::debug(sprintf('Modifier is %d', $modifier));
        foreach ($transactions as $transaction) {
            Log::debug(
                sprintf(
                    'Included %s #%d (part of #%d) ("%s") amount %f',
                    $transaction->transaction_type_type, $transaction->id,
                    $transaction->journal_id
                    , $transaction->description, $transaction->transaction_amount
                )
            );
        }

        $set = Support::filterTransactions($transactions, $accountIds, $modifier);

        return $set;
    }

    /**
     * @param Collection $set
     * @param array      $fields
     *
     * @return array
     */
    protected function groupByFields(Collection $set, array $fields): array
    {
        if (count($fields) === 0 && $this->tags->count() > 0) {
            // do a special group on tags:
            return $this->groupByTag($set);
        }

        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $values = [];
            foreach ($fields as $field) {
                $values[] = intval($transaction->$field);
            }
            $value           = max($values);
            $grouped[$value] = $grouped[$value] ?? '0';
            $grouped[$value] = bcadd($transaction->transaction_amount, $grouped[$value]);
        }

        return $grouped;
    }

    /**
     * @param string $type
     * @param array  $array
     *
     * @return array
     */
    protected function organizeByType(string $type, array $array): array
    {
        $chartData  = [];
        $names      = [];
        $repository = app($this->repositories[$type]);
        $repository->setUser($this->user);
        foreach ($array as $objectId => $amount) {
            if (!isset($names[$objectId])) {
                $object           = $repository->find(intval($objectId));
                $names[$objectId] = $object->name ?? $object->tag;
            }
            $amount                       = Steam::positive($amount);
            $this->total                  = bcadd($this->total, $amount);
            $chartData[$names[$objectId]] = $amount;
        }

        return $chartData;

    }

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByTag(Collection $set): array
    {
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $journal = $transaction->transactionJournal;
            $tags    = $journal->tags;
            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $tagId           = $tag->id;
                $grouped[$tagId] = $grouped[$tagId] ?? '0';
                $grouped[$tagId] = bcadd($transaction->transaction_amount, $grouped[$tagId]);
            }
        }

        return $grouped;
    }
}
